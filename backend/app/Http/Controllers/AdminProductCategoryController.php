<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'categories' => $this->listCategories(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'id' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $label = trim((string) $validated['label']);
        $id = ProductCategory::slugFromLabel($label, $validated['id'] ?? null);

        if (ProductCategory::query()->where('id', $id)->exists()) {
            return response()->json([
                'message' => 'Категория с таким кодом уже существует',
            ], 422);
        }

        $category = ProductCategory::query()->create([
            'id' => $id,
            'label' => $label,
            'sort_order' => ProductCategory::nextSortOrder(),
        ]);

        return response()->json([
            'category' => $category,
            'categories' => $this->listCategories(),
            'message' => 'Категория создана',
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = ProductCategory::query()->findOrFail($id);

        if (Product::query()->where('category', $category->id)->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить категорию: в ней есть товары',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'categories' => $this->listCategories(),
            'message' => 'Категория удалена',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProductCategory>
     */
    private function listCategories()
    {
        return ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label', 'sort_order']);
    }
}
