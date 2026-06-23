<?php

namespace App\Support;

use App\Models\Product;

final class ProductGender
{
    public const BOY = 'boy';

    public const GIRL = 'girl';

    public static function productRequiresChoice(Product $product): bool
    {
        return $product->gender === 'all';
    }

    public static function fixedFromProduct(Product $product): ?string
    {
        return match ($product->gender) {
            'boys' => self::BOY,
            'girls' => self::GIRL,
            default => null,
        };
    }

    /**
     * @return 'boys'|'girls'|null
     */
    public static function toProductGender(?string $selectedGender): ?string
    {
        return match (strtolower(trim((string) $selectedGender))) {
            'boy', 'boys' => 'boys',
            'girl', 'girls' => 'girls',
            default => null,
        };
    }

    public static function normalizeSelected(?string $value, Product $product): string
    {
        $fixed = self::fixedFromProduct($product);
        if ($fixed !== null) {
            return $fixed;
        }

        $normalized = self::normalizeSelectedForItem($value);
        if ($normalized === null) {
            throw new \RuntimeException('Выберите пол');
        }

        return $normalized;
    }

    public static function normalizeSelectedForItem(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'boy', 'boys' => self::BOY,
            'girl', 'girls' => self::GIRL,
            default => null,
        };
    }
}
