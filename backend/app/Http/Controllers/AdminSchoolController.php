<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\UserProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSchoolController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'schools' => $this->listSchools(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        [$city, $name] = $this->validatedSchool($request);

        if ($this->duplicate($city, $name)) {
            return response()->json([
                'message' => 'Такая школа уже есть в этом городе',
            ], 422);
        }

        $school = School::query()->create([
            'city' => $city,
            'name' => $name,
        ]);

        return response()->json([
            'school' => $school,
            'schools' => $this->listSchools(),
            'message' => 'Школа добавлена',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $school = School::query()->findOrFail($id);
        [$city, $name] = $this->validatedSchool($request);

        if ($this->duplicate($city, $name, $school->id)) {
            return response()->json([
                'message' => 'Такая школа уже есть в этом городе',
            ], 422);
        }

        $school->update([
            'city' => $city,
            'name' => $name,
        ]);

        UserProduct::query()
            ->where('school_id', $school->id)
            ->update([
                'school_city' => $school->city,
                'school_name' => $school->name,
            ]);

        return response()->json([
            'school' => $school,
            'schools' => $this->listSchools(),
            'message' => 'Школа обновлена',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $school = School::query()->findOrFail($id);
        $school->delete();

        return response()->json([
            'schools' => $this->listSchools(),
            'message' => 'Школа удалена',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedSchool(Request $request): array
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $city = trim((string) $validated['city']);
        $name = trim((string) $validated['name']);
        if ($city === '' || $name === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'city' => ['Укажите город и название школы'],
            ]);
        }

        return [$city, $name];
    }

    private function duplicate(string $city, string $name, ?int $ignoreId = null): bool
    {
        $cityKey = mb_strtolower($city);
        $nameKey = mb_strtolower($name);

        return School::query()
            ->when($ignoreId !== null, static fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get(['id', 'city', 'name'])
            ->contains(static function (School $school) use ($cityKey, $nameKey): bool {
                return mb_strtolower($school->city) === $cityKey
                    && mb_strtolower($school->name) === $nameKey;
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, School>
     */
    private function listSchools()
    {
        return School::query()
            ->orderBy('city')
            ->orderBy('name')
            ->get(['id', 'city', 'name']);
    }
}
