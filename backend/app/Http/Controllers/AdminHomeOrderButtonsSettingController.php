<?php

namespace App\Http\Controllers;

use App\Support\HomeOrderButtons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHomeOrderButtonsSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(HomeOrderButtons::state());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'custom_size' => ['required', 'boolean'],
            'catalog_order' => ['required', 'boolean'],
        ]);

        HomeOrderButtons::setState(
            (bool) $validated['custom_size'],
            (bool) $validated['catalog_order'],
        );

        return response()->json([
            ...HomeOrderButtons::state(),
            'message' => 'Кнопки на главной сохранены',
        ]);
    }
}
