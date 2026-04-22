<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CartService
{
    private const ALLOWED_SIZES = ['XS', 'S', 'M', 'L', 'XL'];

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

    public function addProduct(int $userId, int $productId, int $quantity = 1, ?string $selectedSize = null): UserProduct
    {
        Product::query()->findOrFail($productId);

        $normalizedSize = $this->normalizeSize($selectedSize);
        if ($normalizedSize === null) {
            throw new \RuntimeException('Выберите размер');
        }

        $item = UserProduct::query()->firstOrNew([
            'user_id' => $userId,
            'product_id' => $productId,
            'selected_size' => $normalizedSize,
        ]);

        $item->quantity = $item->exists
            ? max(1, (int) $item->quantity + $quantity)
            : max(1, $quantity);

        $item->save();

        return $item->fresh('product');
    }

    private function normalizeSize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $size = strtoupper(trim($value));
        if ($size === '' || ! in_array($size, self::ALLOWED_SIZES, true)) {
            return null;
        }

        return $size;
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
