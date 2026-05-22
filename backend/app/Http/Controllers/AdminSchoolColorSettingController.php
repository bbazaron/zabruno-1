<?php

namespace App\Http\Controllers;

use App\Support\ProductSchoolColors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSchoolColorSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'default_school_colors' => ProductSchoolColors::defaults(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_school_colors' => ['required', 'array', 'max:100'],
            'default_school_colors.*' => ['required', 'string', 'max:255'],
            'school_color_renames' => ['sometimes', 'array', 'max:100'],
            'school_color_renames.*.from' => ['required', 'string', 'max:255'],
            'school_color_renames.*.to' => ['required', 'string', 'max:255'],
        ]);

        if (! empty($validated['school_color_renames'])) {
            ProductSchoolColors::applyRenames((array) $validated['school_color_renames']);
        }

        ProductSchoolColors::storeDefaults((array) $validated['default_school_colors']);

        return response()->json([
            'default_school_colors' => ProductSchoolColors::defaults(),
            'message' => 'Общий список школ и цветов сохранён',
        ]);
    }
}
