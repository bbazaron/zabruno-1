<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()->orderByDesc('id')->get();

        return response()->json(['products' => $products]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'gender' => 'required|string|in:boys,girls',
            'season' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'in_stock' => 'sometimes|boolean',
        ]);

        if (! array_key_exists('in_stock', $validated)) {
            $validated['in_stock'] = true;
        }

        $product = Product::create($validated);

        return response()->json(['product' => $product], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:50',
            'gender' => 'sometimes|string|in:boys,girls',
            'season' => 'nullable|string|max:50',
            'price' => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'in_stock' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json(['product' => $product->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Товар удалён']);
    }
}
