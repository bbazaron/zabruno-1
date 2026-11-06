<?php

namespace App\Http\Controllers;

use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ProductSizes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminCheckoutItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = CheckoutItem::query()
            ->with('product')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CheckoutItem $item): array => $this->formatItem($item));

        $usedProductIds = CheckoutItem::query()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $products = Product::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product): array => $this->formatProductOption($product, $usedProductIds));

        return response()->json([
            'items' => $items,
            'products' => $products,
            'categories' => ProductCategory::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['id', 'label', 'sort_order']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request, null);
        $item = DB::transaction(function () use ($request, $validated) {
            $product = $this->resolveProductForSave($request, $validated, null);

            return CheckoutItem::query()->create($this->itemAttributesFromProduct($product, $validated, null));
        });

        return response()->json(['item' => $this->formatItem($item->load('product'))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = CheckoutItem::query()->with('product')->findOrFail($id);
        $validated = $this->validatedPayload($request, $item->id);
        $item = DB::transaction(function () use ($request, $item, $validated) {
            $product = $this->resolveProductForSave($request, $validated, $item);
            $item->update($this->itemAttributesFromProduct($product, $validated, $item));

            return $item->fresh(['product']);
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
            ->with('product')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CheckoutItem $item): array => $this->formatItem($item));

        return response()->json(['items' => $items]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = CheckoutItem::query()->findOrFail($id);
        $this->deleteCheckoutOwnedImage($item->image, $item->product_id);
        $item->delete();

        return response()->json(['message' => 'Карточка удалена']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?int $ignoreItemId): array
    {
        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        }

        $source = $request->input('source', $request->filled('product_id') ? 'catalog' : 'new');
        $request->merge(['source' => $source]);

        $uniqueProduct = Rule::unique('checkout_items', 'product_id');
        if ($ignoreItemId !== null) {
            $uniqueProduct = $uniqueProduct->ignore($ignoreItemId);
        }

        return $request->validate([
            'source' => ['required', 'string', Rule::in(['catalog', 'new'])],
            'product_id' => [
                Rule::requiredIf(fn (): bool => $request->input('source') === 'catalog'),
                'nullable',
                'integer',
                'exists:products,id',
                $uniqueProduct,
            ],
            'name' => [
                Rule::requiredIf(fn (): bool => $request->input('source') === 'new'),
                'nullable',
                'string',
                'max:255',
            ],
            'price' => [
                Rule::requiredIf(fn (): bool => $request->input('source') === 'new'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'gender' => ['sometimes', 'string', Rule::in(['boys', 'girls', 'all'])],
            'category' => [
                Rule::requiredIf(fn (): bool => $request->input('source') === 'new'),
                'nullable',
                'string',
                Rule::exists('product_categories', 'id'),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveProductForSave(Request $request, array $validated, ?CheckoutItem $item): Product
    {
        if (($validated['source'] ?? 'new') === 'catalog') {
            $product = Product::query()->findOrFail((int) $validated['product_id']);
            $this->applyProductFieldUpdates($product, $request, $validated);

            return $product->fresh() ?? $product;
        }

        if ($item?->product_id) {
            $product = $item->product ?? Product::query()->findOrFail((int) $item->product_id);
            $this->applyProductFieldUpdates($product, $request, $validated);
            if (array_key_exists('category', $validated) && is_string($validated['category']) && $validated['category'] !== '') {
                $product->update(['category' => $validated['category']]);
            }

            return $product->fresh() ?? $product;
        }

        $uploaded = $this->storeUploadedImage($request, 'products');
        $gender = $validated['gender'] ?? 'all';

        return Product::query()->create([
            'name' => trim((string) $validated['name']),
            'category' => $validated['category'],
            'gender' => $gender,
            'price' => $validated['price'],
            'sizes' => ProductSizes::defaultList(),
            'image' => $uploaded,
            'in_stock' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyProductFieldUpdates(Product $product, Request $request, array $validated): void
    {
        $updates = [];
        if (array_key_exists('name', $validated) && is_string($validated['name']) && trim($validated['name']) !== '') {
            $updates['name'] = trim($validated['name']);
        }
        if (array_key_exists('price', $validated) && $validated['price'] !== null && $validated['price'] !== '') {
            $updates['price'] = $validated['price'];
        }
        if (array_key_exists('gender', $validated) && is_string($validated['gender'])) {
            $updates['gender'] = $validated['gender'];
        }
        $uploaded = $this->storeUploadedImage($request, 'products');
        if ($uploaded !== null) {
            $updates['image'] = $uploaded;
        }
        if ($updates !== []) {
            $product->update($updates);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function itemAttributesFromProduct(Product $product, array $validated, ?CheckoutItem $existing): array
    {
        $sortOrder = $existing?->sort_order;
        if (array_key_exists('sort_order', $validated)) {
            $sortOrder = (int) $validated['sort_order'];
        } elseif ($sortOrder === null) {
            $max = CheckoutItem::query()->max('sort_order');
            $sortOrder = is_numeric($max) ? ((int) $max) + 1 : 0;
        }

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'image' => $product->image,
            'gender' => $product->gender,
            'sort_order' => $sortOrder,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : true,
        ];
    }

    private function storeUploadedImage(Request $request, string $directory): ?string
    {
        $file = $request->file('image_file');
        if ($file === null) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    private function deleteCheckoutOwnedImage(?string $path, ?int $productId): void
    {
        $path = trim((string) $path);
        if ($path === '' || $productId !== null || preg_match('/^https?:\/\//i', $path) === 1) {
            return;
        }
        if (! str_starts_with(ltrim($path, '/'), 'checkout-items/')) {
            return;
        }
        $relative = preg_replace('#^/?storage/#', '', $path) ?? $path;
        Storage::disk('public')->delete($relative);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatItem(CheckoutItem $item): array
    {
        $item->loadMissing('product');

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->resolvedName(),
            'price' => $item->resolvedPrice(),
            'image' => $item->image,
            'image_url' => $item->resolvedImageUrl(),
            'gender' => $item->resolvedGender(),
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
            'source' => $item->product_id ? 'catalog' : 'new',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  list<int>  $usedProductIds
     * @return array<string, mixed>
     */
    private function formatProductOption(Product $product, array $usedProductIds): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'gender' => $product->gender,
            'category' => $product->category,
            'image' => $product->image,
            'image_url' => (new CheckoutItem(['image' => $product->image]))->toPublicMediaUrl($product->image),
            'in_use' => in_array($product->id, $usedProductIds, true),
        ];
    }
}
