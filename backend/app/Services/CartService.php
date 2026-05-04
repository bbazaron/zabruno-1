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

        $normalizedSize = $this->normalizeSize($selectedSize);
        if ($normalizedSize === null) {
            throw new \RuntimeException('Выберите размер');
        }
        $normalizedColor = $this->normalizeColor($selectedColor, (string) ($product->color ?? ''));
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

    private function normalizeColor(?string $value, string $rawProductColors): ?string
    {
        $options = $this->extractColorOptions($rawProductColors);
        if ($options === []) {
            throw new \RuntimeException('Для товара не настроены доступные цвета');
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            throw new \RuntimeException('Выберите цвет');
        }

        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower($normalized)) {
                return $option;
            }
        }

        throw new \RuntimeException('Выбранный цвет недоступен');
    }

    private function normalizeClass(?string $value): ?string
    {
        $raw = mb_strtoupper(trim((string) ($value ?? '')));
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/\s+/u', '', $raw) ?? '';
        if ($raw === '' || preg_match('/^\d{1,2}[А-ЯA-Z]$/u', $raw) !== 1) {
            throw new \RuntimeException('Формат надписи должен быть вида 10Б');
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    private function extractColorOptions(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[,\n;|]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value === '') {
                continue;
            }
            if (! in_array($value, $out, true)) {
                $out[] = $value;
            }
        }

        return $out;
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
