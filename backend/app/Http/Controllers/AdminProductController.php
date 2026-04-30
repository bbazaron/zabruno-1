<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with('media')
            ->orderByDesc('id')
            ->get();

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
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (! array_key_exists('in_stock', $validated)) {
            $validated['in_stock'] = true;
        }

        $product = DB::transaction(function () use ($request, $validated) {
            $product = Product::create($validated);
            $this->storeUploadedMedia($product, $request);
            $this->refreshPrimaryImage($product);

            return $product->fresh(['media']);
        });

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
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_media_ids' => 'sometimes|array',
            'remove_media_ids.*' => 'integer',
        ]);

        $product = DB::transaction(function () use ($request, $product, $validated) {
            $product->update($validated);

            $removeIds = array_map(
                'intval',
                (array) $request->input('remove_media_ids', [])
            );
            if ($removeIds !== []) {
                $mediaToRemove = $product->media()->whereIn('id', $removeIds)->get();
                foreach ($mediaToRemove as $media) {
                    Storage::disk('public')->delete($media->path);
                    $media->delete();
                }
            }

            $this->storeUploadedMedia($product, $request);
            $this->refreshPrimaryImage($product);

            return $product->fresh(['media']);
        });

        return response()->json(['product' => $product]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $mediaItems = $product->media()->get();

        foreach ($mediaItems as $media) {
            Storage::disk('public')->delete($media->path);
        }

        $product->delete();

        return response()->json(['message' => 'Товар удалён']);
    }

    private function storeUploadedMedia(Product $product, Request $request): void
    {
        if (! $request->hasFile('media')) {
            return;
        }

        $nextSortOrder = (int) ($product->media()->max('sort_order') ?? -1) + 1;
        foreach ((array) $request->file('media', []) as $file) {
            if (! $file) {
                continue;
            }
            $path = $file->store('products', 'public');
            $product->media()->create([
                'path' => $path,
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    private function refreshPrimaryImage(Product $product): void
    {
        $firstMedia = $product->media()->orderBy('sort_order')->first();
        $fallbackImage = $product->image;
        $product->image = $firstMedia?->path ?? $fallbackImage;
        $product->save();
    }
}
