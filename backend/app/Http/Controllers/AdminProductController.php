<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductSchoolColors;
use App\Support\ProductSizes;
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
            'color' => 'nullable|string',
            'school_colors' => 'sometimes|array|max:50',
            'school_colors.*' => 'required|string|max:255',
            'sizes' => 'required|array|min:1|max:30',
            'sizes.*' => 'required|string|max:16',
            'image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'in_stock' => 'sometimes|boolean',
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (! array_key_exists('in_stock', $validated)) {
            $validated['in_stock'] = true;
        }
        $validated['color'] = $this->resolveSchoolColors($request, $validated['color'] ?? null);
        unset($validated['school_colors']);
        $validated['sizes'] = $this->normalizeSizesList($validated['sizes'] ?? []);
        $validated['image'] = $this->sanitizeImagePath($validated['image'] ?? null);

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
            'color' => 'nullable|string',
            'school_colors' => 'sometimes|array|max:50',
            'school_colors.*' => 'required|string|max:255',
            'sizes' => 'sometimes|array|min:1|max:30',
            'sizes.*' => 'required|string|max:16',
            'image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'in_stock' => 'sometimes|boolean',
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_media_ids' => 'sometimes|array',
            'remove_media_ids.*' => 'integer',
        ]);
        if (array_key_exists('image', $validated)) {
            $validated['image'] = $this->sanitizeImagePath($validated['image']);
        }
        if ($request->has('school_colors') || array_key_exists('color', $validated)) {
            $validated['color'] = $this->resolveSchoolColors($request, $validated['color'] ?? null);
        }
        unset($validated['school_colors']);
        if (array_key_exists('sizes', $validated)) {
            $validated['sizes'] = $this->normalizeSizesList($validated['sizes']);
        }

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
        $fallbackImage = $this->sanitizeImagePath($product->image);
        $product->image = $firstMedia?->path ?? $fallbackImage;
        $product->save();
    }

    private function sanitizeImagePath(?string $path): ?string
    {
        $value = trim((string) ($path ?? ''));
        if ($value === '' || $value === '0' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function resolveSchoolColors(Request $request, ?string $fallbackColor): ?string
    {
        $fromArray = $request->input('school_colors');
        if (is_array($fromArray) && $fromArray !== []) {
            return ProductSchoolColors::serializeList($fromArray);
        }

        if ($fallbackColor === null) {
            return null;
        }

        return ProductSchoolColors::normalizeStored($fallbackColor);
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<string>
     */
    private function normalizeSizesList(array $raw): array
    {
        $normalized = ProductSizes::normalizeList($raw);

        return $normalized !== [] ? $normalized : ProductSizes::defaultList();
    }
}
