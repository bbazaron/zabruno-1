<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ProductSchoolColors;
use App\Support\ProductSizes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with('media')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Product $product): array => $this->formatAdminProduct($product));

        return response()->json([
            'products' => $products,
            'default_school_colors' => ProductSchoolColors::defaults(),
            'categories' => ProductCategory::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['id', 'label', 'sort_order']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['required', 'string', Rule::exists('product_categories', 'id')],
            'gender' => 'required|string|in:boys,girls',
            'season' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'school_color_excluded' => 'sometimes|array|max:100',
            'school_color_excluded.*' => 'required|string|max:255',
            'school_color_extra' => 'sometimes|array|max:50',
            'school_color_extra.*' => 'required|string|max:255',
            'school_colors' => 'sometimes|array|max:50',
            'school_colors.*' => 'required|string|max:255',
            'sizes' => 'required|array|min:1|max:30',
            'sizes.*' => 'required|string|max:16',
            'image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'in_stock' => 'sometimes|boolean',
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'media_sequence' => 'sometimes|array|max:10',
            'media_sequence.*' => ['required', 'string', 'regex:/^(id:\d+|file:\d+)$/'],
            'media_catalog_zoom' => 'sometimes|array',
            'media_catalog_zoom.*' => 'integer|min:100|max:250',
            'media_catalog_pan_x' => 'sometimes|array',
            'media_catalog_pan_x.*' => 'integer|min:-100|max:100',
            'media_catalog_pan_y' => 'sometimes|array',
            'media_catalog_pan_y.*' => 'integer|min:-100|max:100',
        ]);

        if (! array_key_exists('in_stock', $validated)) {
            $validated['in_stock'] = true;
        }
        $validated = array_merge($validated, $this->resolveSchoolColorFields($request));
        unset($validated['school_colors']);
        $validated['sizes'] = $this->normalizeSizesList($validated['sizes'] ?? []);
        $validated['image'] = $this->sanitizeImagePath($validated['image'] ?? null);

        $product = DB::transaction(function () use ($request, $validated) {
            $product = Product::create($validated);
            $this->applyMediaSequence($product, $request);
            $this->applyCatalogMediaSettings($product, $request);
            $this->refreshPrimaryImage($product);

            return $product->fresh(['media']);
        });

        return response()->json(['product' => $this->formatAdminProduct($product)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => ['sometimes', 'string', Rule::exists('product_categories', 'id')],
            'gender' => 'sometimes|string|in:boys,girls',
            'season' => 'nullable|string|max:50',
            'price' => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'school_color_excluded' => 'sometimes|array|max:100',
            'school_color_excluded.*' => 'required|string|max:255',
            'school_color_extra' => 'sometimes|array|max:50',
            'school_color_extra.*' => 'required|string|max:255',
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
            'media_sequence' => 'sometimes|array|max:10',
            'media_sequence.*' => ['required', 'string', 'regex:/^(id:\d+|file:\d+)$/'],
            'media_catalog_zoom' => 'sometimes|array',
            'media_catalog_zoom.*' => 'integer|min:100|max:250',
            'media_catalog_pan_x' => 'sometimes|array',
            'media_catalog_pan_x.*' => 'integer|min:-100|max:100',
            'media_catalog_pan_y' => 'sometimes|array',
            'media_catalog_pan_y.*' => 'integer|min:-100|max:100',
        ]);
        if (array_key_exists('image', $validated)) {
            $validated['image'] = $this->sanitizeImagePath($validated['image']);
        }
        // Always apply school/color config on admin update (empty excluded[] is omitted from FormData).
        $validated = array_merge($validated, $this->resolveSchoolColorFields($request));
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

            $this->applyMediaSequence($product, $request);
            $this->applyCatalogMediaSettings($product, $request);
            $this->refreshPrimaryImage($product);

            return $product->fresh(['media']);
        });

        return response()->json(['product' => $this->formatAdminProduct($product)]);
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

    /**
     * @return array<string, mixed>
     */
    private function formatAdminProduct(Product $product): array
    {
        $data = $product->toArray();
        $data['school_color_excluded'] = ProductSchoolColors::parseList($product->school_color_excluded);
        $data['school_color_extra'] = ProductSchoolColors::parseList($product->school_color_extra);
        $data['color'] = ProductSchoolColors::serializeList($product->schoolColorsList());

        return $data;
    }

    /**
     * @return array{color: ?string, school_color_excluded: ?string, school_color_extra: ?string}
     */
    private function resolveSchoolColorFields(Request $request): array
    {
        if (
            $request->has('school_colors')
            && ! $request->has('school_color_excluded')
            && ! $request->has('school_color_extra')
        ) {
            $legacy = $request->input('school_colors');
            $config = is_array($legacy)
                ? ProductSchoolColors::productConfigFromLegacyColor(
                    ProductSchoolColors::serializeList($legacy),
                )
                : ['excluded' => [], 'extra' => []];
            $excluded = $config['excluded'];
            $extra = $config['extra'];
        } else {
            $excluded = ProductSchoolColors::parseList($request->input('school_color_excluded'));
            $extra = ProductSchoolColors::parseList($request->input('school_color_extra'));
        }

        $effective = ProductSchoolColors::mergeConfig($excluded, $extra);

        return [
            'school_color_excluded' => ProductSchoolColors::serializeList($excluded),
            'school_color_extra' => ProductSchoolColors::serializeList($extra),
            'color' => ProductSchoolColors::serializeList($effective),
        ];
    }

    private function applyMediaSequence(Product $product, Request $request): void
    {
        $sequence = array_values(array_filter(
            (array) $request->input('media_sequence', []),
            static fn ($slot): bool => is_string($slot) && $slot !== '',
        ));

        if ($sequence === []) {
            $this->storeUploadedMedia($product, $request);

            return;
        }

        $files = array_values(array_filter((array) $request->file('media', [])));
        $sortOrder = 0;

        foreach ($sequence as $slot) {
            if (preg_match('/^id:(\d+)$/', (string) $slot, $matches)) {
                $media = $product->media()->find((int) $matches[1]);
                if ($media) {
                    $media->update(['sort_order' => $sortOrder++]);
                }

                continue;
            }

            if (! preg_match('/^file:(\d+)$/', (string) $slot, $matches)) {
                continue;
            }

            $file = $files[(int) $matches[1]] ?? null;
            if (! $file) {
                continue;
            }

            $path = $file->store('products', 'public');
            $fileIndex = (int) ($matches[1] ?? 0);
            $product->media()->create(array_merge(
                [
                    'path' => $path,
                    'sort_order' => $sortOrder++,
                ],
                $this->catalogCropAttributesFromRequest($request, 'new:'.$fileIndex),
            ));
        }
    }

    private function applyCatalogMediaSettings(Product $product, Request $request): void
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

            $product->media()->where('id', $mediaId)->update($updates);
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
            $updates['catalog_zoom'] = $this->clampCatalogZoom($zoomById[$key]);
        }
        if (array_key_exists($key, $panXById)) {
            $updates['catalog_pan_x'] = $this->clampCatalogPan($panXById[$key]);
        }
        if (array_key_exists($key, $panYById)) {
            $updates['catalog_pan_y'] = $this->clampCatalogPan($panYById[$key]);
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

    private function clampCatalogZoom(mixed $value): int
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

    private function clampCatalogPan(mixed $value): int
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

    private function storeUploadedMedia(Product $product, Request $request): void
    {
        if (! $request->hasFile('media')) {
            return;
        }

        $nextSortOrder = (int) ($product->media()->max('sort_order') ?? -1) + 1;
        $fileIndex = 0;
        foreach ((array) $request->file('media', []) as $file) {
            if (! $file) {
                continue;
            }
            $path = $file->store('products', 'public');
            $product->media()->create(array_merge(
                [
                    'path' => $path,
                    'sort_order' => $nextSortOrder++,
                ],
                $this->catalogCropAttributesFromRequest($request, 'new:'.$fileIndex),
            ));
            $fileIndex++;
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
