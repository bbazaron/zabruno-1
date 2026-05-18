<?php

namespace App\Support;

final class ProductSchoolColors
{
    private const DELIMITER = "\n";

    /**
     * @return list<string>
     */
    public static function parseList(mixed $raw): array
    {
        if (is_array($raw)) {
            return self::normalizeList($raw);
        }

        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return [];
        }

        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            $parts = preg_split('/\R+/u', $value) ?: [];
        } else {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return self::normalizeList($decoded);
            }

            // Single option; commas are part of the label (e.g. "Школа №2, белый").
            $parts = [$value];
        }

        return self::normalizeList($parts);
    }

    /**
     * @param  list<string>  $items
     */
    public static function serializeList(array $items): ?string
    {
        $normalized = self::normalizeList($items);
        if ($normalized === []) {
            return null;
        }

        return implode(self::DELIMITER, $normalized);
    }

    public static function normalizeStored(?string $raw): ?string
    {
        return self::serializeList(self::parseList($raw));
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    private static function normalizeList(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '' || in_array($value, $out, true)) {
                continue;
            }
            $out[] = $value;
        }

        return $out;
    }
}
