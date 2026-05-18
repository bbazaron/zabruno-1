<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UserProduct;
use App\Support\ProductSchoolColors;
use App\Support\ProductSizes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CartService
{
    /**
     * @return EloquentCollection<int, UserProduct>
     */
    public function getCartItems(int $userId): EloquentCollection
    {
        return UserProduct::query()
            ->with('product')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function addProduct(
        int $userId,
        int $productId,
        int $quantity = 1,
        ?string $selectedSize = null,
        ?string $selectedColor = null,
        ?string $selectedClass = null,
    ): UserProduct
    {
        $product = Product::query()->findOrFail($productId);

        $normalizedSize = $this->normalizeSize($selectedSize, $product);
        $normalizedColor = $this->normalizeColor($selectedColor, $product);
        $normalizedClass = $this->normalizeClass($selectedClass);

        $item = UserProduct::query()->firstOrNew([
            'user_id' => $userId,
            'product_id' => $productId,
            'selected_size' => $normalizedSize,
            'selected_color' => $normalizedColor,
            'selected_class' => $normalizedClass,
        ]);

        $item->quantity = $item->exists
            ? max(1, (int) $item->quantity + $quantity)
            : max(1, $quantity);

        $item->save();

        return $item->fresh('product');
    }

    private function normalizeSize(?string $value, Product $product): ?string
    {
        $allowed = $product->sizesList();
        if ($allowed === []) {
            return null;
        }

        $normalized = ProductSizes::normalizeOne($value);
        if ($normalized !== null && ProductSizes::isAllowed($normalized, $allowed)) {
            return $normalized;
        }

        return $allowed[0];
    }

    private function normalizeColor(?string $value, Product $product): ?string
    {
        $options = $product->schoolColorsList();
        if ($options === []) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return $options[0];
        }

        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower($normalized)) {
                return $option;
            }
        }

        // Custom value from "Другое" on the product page.
        if ($normalized !== '' && mb_strlen($normalized) <= 255) {
            return $normalized;
        }

        return $options[0];
    }

    private function normalizeClass(?string $value): ?string
    {
        $raw = mb_strtoupper(trim((string) ($value ?? '')));
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/\s+/u', '', $raw) ?? '';
        if ($raw === '' || preg_match('/^\d{1,2}[А-ЯA-Z]$/u', $raw) !== 1) {
            return null;
        }

        return $raw;
    }

    public function updateQuantity(int $userId, int $itemId, int $quantity): ?UserProduct
    {
        $item = UserProduct::query()
            ->where('user_id', $userId)
            ->where('id', $itemId)
            ->first();

        if ($item === null) {
            return null;
        }

        $item->update([
            'quantity' => max(1, $quantity),
        ]);

        return $item->fresh('product');
    }

    public function removeItem(int $userId, int $itemId): bool
    {
        $item = UserProduct::query()
            ->where('user_id', $userId)
            ->where('id', $itemId)
            ->first();

        if ($item === null) {
            return false;
        }

        return (bool) $item->delete();
    }
}
