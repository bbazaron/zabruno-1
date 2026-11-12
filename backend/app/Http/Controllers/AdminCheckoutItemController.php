<?php

namespace App\Http\Controllers;

use App\Models\CheckoutItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCheckoutItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = CheckoutItem::query()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CheckoutItem $item): array => $this->formatItem($item));

        $products = Product::query()
            ->with('media')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product): array => $this->formatCatalogSample($product));

        return response()->json([
            'items' => $items,
            'products' => $products,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $item = DB::transaction(function () use ($request, $validated) {
            $item = CheckoutItem::query()->create($this->itemAttributes($validated, null));
            $sequence = array_values(array_filter(
                (array) $request->input('media_sequence', []),
                static fn ($slot): bool => is_string($slot) && $slot !== '',
            ));
            $copiesChosenPhotos = collect($sequence)->contains(
                static fn (string $slot): bool => str_starts_with($slot, 'source:'),
            );
            if (
                ! empty($validated['copy_from_product_id'])
                && ($validated['copy_media'] ?? true)
                && ! $copiesChosenPhotos
            ) {
                $this->copyProductMedia($item, (int) $validated['copy_from_product_id']);
            }
            $this->applyMediaSequence($item, $request);
            $this->applyCatalogMediaSettings($item, $request);
            $this->refreshPrimaryImage($item);
            $this->assertMediaLimit($item);

            return $item->fresh(['media']);
        });

        return response()->json(['item' => $this->formatItem($item)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = CheckoutItem::query()->with('media')->findOrFail($id);
        $validated = $this->validatedPayload($request);

        $item = DB::transaction(function () use ($request, $item, $validated) {
            $item->update($this->itemAttributes($validated, $item));

            $removeIds = array_map('intval', (array) $request->input('remove_media_ids', []));
            if ($removeIds !== []) {
                $mediaToRemove = $item->media()->whereIn('id', $removeIds)->get();
                foreach ($mediaToRemove as $media) {
                    $this->deleteOwnedFile($media->path);
                    $media->delete();
                }
            }

            $this->applyMediaSequence($item, $request);
            $this->applyCatalogMediaSettings($item, $request);
            $this->refreshPrimaryImage($item);
            $this->assertMediaLimit($item);

            return $item->fresh(['media']);
        });

        return response()->json(['item' => $this->formatItem($item)]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:checkout_items,id',
        ]);

        DB::transaction(function () use ($validated): void {
            foreach (array_values($validated['ids']) as $index => $id) {
                CheckoutItem::query()->whereKey($id)->update(['sort_order' => $index]);
            }
        });

        $items = CheckoutItem::query()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CheckoutItem $item): array => $this->formatItem($item));

        return response()->json(['items' => $items]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = CheckoutItem::query()->with('media')->findOrFail($id);
        foreach ($item->media as $media) {
            $this->deleteOwnedFile($media->path);
        }
        $this->deleteOwnedFile($item->image);
        $item->delete();

        return response()->json(['message' => 'Карточка удалена']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        }
        if ($request->has('copy_media')) {
            $request->merge(['copy_media' => $request->boolean('copy_media')]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'gender' => ['sometimes', 'string', Rule::in(['boys', 'girls', 'all'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'copy_from_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'copy_media' => ['sometimes', 'boolean'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_media_ids' => ['sometimes', 'array'],
            'remove_media_ids.*' => ['integer'],
            'media_sequence' => ['sometimes', 'array', 'max:10'],
            'media_sequence.*' => ['required', 'string', 'regex:/^(id:\d+|file:\d+|source:\d+|source:image)$/'],
            'media_catalog_zoom' => ['sometimes', 'array'],
            'media_catalog_zoom.*' => ['integer', 'min:100', 'max:250'],
            'media_catalog_pan_x' => ['sometimes', 'array'],
            'media_catalog_pan_x.*' => ['integer', 'min:-100', 'max:100'],
            'media_catalog_pan_y' => ['sometimes', 'array'],
            'media_catalog_pan_y.*' => ['integer', 'min:-100', 'max:100'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function itemAttributes(array $validated, ?CheckoutItem $existing): array
    {
        $sortOrder = $existing?->sort_order;
        if (array_key_exists('sort_order', $validated)) {
            $sortOrder = (int) $validated['sort_order'];
        } elseif ($sortOrder === null) {
            $max = CheckoutItem::query()->max('sort_order');
            $sortOrder = is_numeric($max) ? ((int) $max) + 1 : 0;
        }

        return [
            'name' => trim((string) $validated['name']),
            'price' => $validated['price'],
            'description' => array_key_exists('description', $validated)
                ? $this->nullableText($validated['description'])
                : $existing?->description,
            'gender' => $validated['gender'] ?? $existing?->gender ?? 'all',
            'sort_order' => $sortOrder,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : ($existing?->is_active ?? true),
        ];
    }

    private function copyProductMedia(CheckoutItem $item, int $productId): void
    {
        $product = Product::query()->with('media')->find($productId);
        if ($product === null) {
            return;
        }

        $sources = $product->media;
        if ($sources->isEmpty() && is_string($product->image) && trim($product->image) !== '') {
            $copied = $this->copyPublicFile($product->image);
            if ($copied !== null) {
                $item->media()->create([
                    'path' => $copied,
                    'sort_order' => 0,
                    'catalog_zoom' => 100,
                    'catalog_pan_x' => 0,
                    'catalog_pan_y' => 0,
                ]);
            }

            return;
        }

        $sort = (int) ($item->media()->max('sort_order') ?? -1) + 1;
        foreach ($sources as $media) {
            $copied = $this->copyPublicFile($media->path);
            if ($copied === null) {
                continue;
            }
            $item->media()->create([
                'path' => $copied,
                'sort_order' => $sort++,
                'catalog_zoom' => $this->clampZoom($media->catalog_zoom),
                'catalog_pan_x' => $this->clampPan($media->catalog_pan_x),
                'catalog_pan_y' => $this->clampPan($media->catalog_pan_y),
            ]);
        }
    }

    private function applyMediaSequence(CheckoutItem $item, Request $request): void
    {
        $sequence = array_values(array_filter(
            (array) $request->input('media_sequence', []),
            static fn ($slot): bool => is_string($slot) && $slot !== '',
        ));

        if ($sequence === []) {
            $this->storeUploadedMedia($item, $request);

            return;
        }

        $files = array_values(array_filter(
            (array) $request->file('media', []),
            static fn ($file): bool => $file instanceof UploadedFile,
        ));
        $mentionsExisting = false;
        foreach ($sequence as $slot) {
            if (str_starts_with((string) $slot, 'id:')) {
                $mentionsExisting = true;
                break;
            }
        }
        $sortOrder = $mentionsExisting
            ? 0
            : ((int) ($item->media()->max('sort_order') ?? -1) + 1);

        foreach ($sequence as $slot) {
            if (preg_match('/^id:(\d+)$/', (string) $slot, $matches)) {
                $media = $item->media()->find((int) $matches[1]);
                if ($media) {
                    $media->update(['sort_order' => $sortOrder++]);
                }

                continue;
            }

            if (str_starts_with((string) $slot, 'source:')) {
                if ($this->copySourceSlot($item, $request, (string) $slot, $sortOrder)) {
                    $sortOrder++;
                }

                continue;
            }

            if (! preg_match('/^file:(\d+)$/', (string) $slot, $matches)) {
                continue;
            }

            $file = $files[(int) $matches[1]] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('checkout-items', 'public');
            $item->media()->create(array_merge(
                [
                    'path' => $path,
                    'sort_order' => $sortOrder++,
                ],
                $this->catalogCropAttributesFromRequest($request, 'new:'.(int) $matches[1]),
            ));
        }
    }

    private function copySourceSlot(CheckoutItem $item, Request $request, string $slot, int $sortOrder): bool
    {
        $productId = (int) $request->input('copy_from_product_id');
        if ($productId <= 0) {
            return false;
        }
        $product = Product::query()->with('media')->find($productId);
        if ($product === null) {
            return false;
        }

        if ($slot === 'source:image') {
            if (! is_string($product->image) || trim($product->image) === '') {
                return false;
            }
            $copied = $this->copyPublicFile($product->image);
            if ($copied === null) {
                return false;
            }
            $item->media()->create(array_merge(
                [
                    'path' => $copied,
                    'sort_order' => $sortOrder,
                ],
                $this->catalogCropAttributesFromRequest($request, 'source:image'),
            ));

            return true;
        }

        if (! preg_match('/^source:(\d+)$/', $slot, $matches)) {
            return false;
        }
        $source = $product->media->firstWhere('id', (int) $matches[1]);
        if ($source === null) {
            return false;
        }
        $copied = $this->copyPublicFile($source->path);
        if ($copied === null) {
            return false;
        }
        $item->media()->create(array_merge(
            [
                'path' => $copied,
                'sort_order' => $sortOrder,
            ],
            $this->catalogCropAttributesFromRequest($request, 'source:'.$source->id),
        ));

        return true;
    }

    private function storeUploadedMedia(CheckoutItem $item, Request $request): void
    {
        if (! $request->hasFile('media')) {
            return;
        }

        $nextSortOrder = (int) ($item->media()->max('sort_order') ?? -1) + 1;
        $fileIndex = 0;
        foreach ((array) $request->file('media', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('checkout-items', 'public');
            $item->media()->create(array_merge(
                [
                    'path' => $path,
                    'sort_order' => $nextSortOrder++,
                ],
                $this->catalogCropAttributesFromRequest($request, 'new:'.$fileIndex),
            ));
            $fileIndex++;
        }
    }

    private function applyCatalogMediaSettings(CheckoutItem $item, Request $request): void
    {
        $zoomById = (array) $request->input('media_catalog_zoom', []);
        $panXById = (array) $request->input('media_catalog_pan_x', []);
        $panYById = (array) $request->input('media_catalog_pan_y', []);
        $ids = array_unique(array_merge(array_keys($zoomById), array_keys($panXById), array_keys($panYById)));

        foreach ($ids as $id) {
            if (is_string($id) && preg_match('/^new:\d+$/', $id) === 1) {
                continue;
            }

            $mediaId = (int) $id;
            if ($mediaId <= 0) {
                continue;
            }

            $updates = $this->catalogCropUpdatesFromRequest($request, (string) $id);
            if ($updates === []) {
                continue;
            }

            $item->media()->where('id', $mediaId)->update($updates);
        }
    }

    /**
     * @return array<string, int>
     */
    private function catalogCropUpdatesFromRequest(Request $request, string $key): array
    {
        $zoomById = (array) $request->input('media_catalog_zoom', []);
        $panXById = (array) $request->input('media_catalog_pan_x', []);
        $panYById = (array) $request->input('media_catalog_pan_y', []);

        $updates = [];
        if (array_key_exists($key, $zoomById)) {
            $updates['catalog_zoom'] = $this->clampZoom($zoomById[$key]);
        }
        if (array_key_exists($key, $panXById)) {
            $updates['catalog_pan_x'] = $this->clampPan($panXById[$key]);
        }
        if (array_key_exists($key, $panYById)) {
            $updates['catalog_pan_y'] = $this->clampPan($panYById[$key]);
        }

        return $updates;
    }

    /**
     * @return array{catalog_zoom: int, catalog_pan_x: int, catalog_pan_y: int}
     */
    private function catalogCropAttributesFromRequest(Request $request, string $key): array
    {
        $updates = $this->catalogCropUpdatesFromRequest($request, $key);

        return [
            'catalog_zoom' => $updates['catalog_zoom'] ?? 100,
            'catalog_pan_x' => $updates['catalog_pan_x'] ?? 0,
            'catalog_pan_y' => $updates['catalog_pan_y'] ?? 0,
        ];
    }

    private function assertMediaLimit(CheckoutItem $item): void
    {
        if ($item->media()->count() > 10) {
            throw ValidationException::withMessages([
                'media' => 'Можно сохранить не больше 10 фотографий.',
            ]);
        }
    }

    private function refreshPrimaryImage(CheckoutItem $item): void
    {
        $firstMedia = $item->media()->orderBy('sort_order')->orderBy('id')->first();
        $item->image = $firstMedia?->path;
        $item->save();
    }

    private function copyPublicFile(string $path): ?string
    {
        $relative = preg_replace('#^/?storage/#', '', trim($path)) ?? '';
        $relative = ltrim($relative, '/');
        if ($relative === '' || preg_match('/^https?:\/\//i', $path) === 1) {
            return null;
        }
        if (! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $extension = pathinfo($relative, PATHINFO_EXTENSION);
        $target = 'checkout-items/'.uniqid('copy_', true).($extension !== '' ? '.'.$extension : '');
        Storage::disk('public')->copy($relative, $target);

        return $target;
    }

    private function deleteOwnedFile(?string $path): void
    {
        $relative = preg_replace('#^/?storage/#', '', trim((string) $path)) ?? '';
        $relative = ltrim($relative, '/');
        if ($relative === '' || ! str_starts_with($relative, 'checkout-items/')) {
            return;
        }
        Storage::disk('public')->delete($relative);
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $text = trim($value);

        return $text === '' ? null : $text;
    }

    private function clampZoom(mixed $value): int
    {
        $zoom = (int) $value;
        if ($zoom < 100) {
            return 100;
        }
        if ($zoom > 250) {
            return 250;
        }

        return $zoom;
    }

    private function clampPan(mixed $value): int
    {
        $pan = (int) $value;
        if ($pan < -100) {
            return -100;
        }
        if ($pan > 100) {
            return 100;
        }

        return $pan;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatItem(CheckoutItem $item): array
    {
        $item->loadMissing('media');

        return [
            'id' => $item->id,
            'name' => $item->resolvedName(),
            'price' => $item->resolvedPrice(),
            'description' => $item->description,
            'image' => $item->image,
            'image_url' => $item->resolvedImageUrl(),
            'media' => $item->media->map(fn ($media): array => [
                'id' => $media->id,
                'path' => $media->path,
                'url' => $item->toPublicMediaUrl($media->path),
                'sort_order' => $media->sort_order,
                'catalog_zoom' => $this->clampZoom($media->catalog_zoom),
                'catalog_pan_x' => $this->clampPan($media->catalog_pan_x),
                'catalog_pan_y' => $this->clampPan($media->catalog_pan_y),
            ])->values()->all(),
            'gender' => $item->resolvedGender(),
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCatalogSample(Product $product): array
    {
        $product->loadMissing('media');
        $urlFor = fn (?string $path): ?string => (new CheckoutItem)->toPublicMediaUrl($path);
        $media = $product->media
            ->map(function ($media) use ($urlFor): ?array {
                $url = $urlFor($media->path);
                if ($url === null) {
                    return null;
                }

                return [
                    'id' => $media->id,
                    'url' => $url,
                    'catalog_zoom' => $this->clampZoom($media->catalog_zoom),
                    'catalog_pan_x' => $this->clampPan($media->catalog_pan_x),
                    'catalog_pan_y' => $this->clampPan($media->catalog_pan_y),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($media === []) {
            $fallback = $urlFor($product->image);
            if ($fallback !== null) {
                $media[] = [
                    'id' => null,
                    'url' => $fallback,
                    'catalog_zoom' => 100,
                    'catalog_pan_x' => 0,
                    'catalog_pan_y' => 0,
                ];
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'gender' => $product->gender,
            'description' => $product->description,
            'image_url' => $media[0]['url'] ?? $urlFor($product->image),
            'media' => $media,
        ];
    }
}
