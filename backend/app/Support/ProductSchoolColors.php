<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;

final class ProductSchoolColors
{
    public const SETTING_KEY = 'default_school_colors';

    private const DELIMITER = "\n";

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return self::sortList(self::parseList(Setting::getValue(self::SETTING_KEY, '')));
    }

    public static function storeDefaults(array $items): void
    {
        Setting::setValue(self::SETTING_KEY, self::serializeList(self::sortList($items)) ?? '');
    }

    /**
     * @param  list<array{from: string, to: string}>  $renames
     */
    public static function applyRenames(array $renames): void
    {
        $map = [];
        foreach ($renames as $row) {
            $from = trim((string) ($row['from'] ?? ''));
            $to = trim((string) ($row['to'] ?? ''));
            if ($from === '' || $to === '' || $from === $to) {
                continue;
            }
            $map[$from] = $to;
        }

        if ($map === []) {
            return;
        }

        Product::query()->chunkById(100, static function ($products) use ($map): void {
            foreach ($products as $product) {
                $excluded = self::parseList($product->school_color_excluded);
                $extra = self::parseList($product->school_color_extra);
                $legacy = self::parseList($product->color);

                $newExcluded = self::renameInList($excluded, $map);
                $newExtra = self::renameInList($extra, $map);
                $newLegacy = self::renameInList($legacy, $map);

                $excludedChanged = $newExcluded !== $excluded;
                $extraChanged = $newExtra !== $extra;
                $legacyChanged = $newLegacy !== $legacy;

                if (! $excludedChanged && ! $extraChanged && ! $legacyChanged) {
                    continue;
                }

                if ($excludedChanged) {
                    $product->school_color_excluded = self::serializeList($newExcluded);
                }
                if ($extraChanged) {
                    $product->school_color_extra = self::serializeList($newExtra);
                }
                if ($legacyChanged) {
                    $product->color = self::serializeList($newLegacy);
                }

                $product->save();
            }
        });
    }

    /**
     * @return list<string>
     */
    public static function forProduct(Product $product): array
    {
        $defaults = self::defaults();
        $excluded = self::parseList($product->school_color_excluded);
        $extra = self::parseList($product->school_color_extra);
        $legacy = self::parseList($product->color);

        if ($excluded === [] && $extra === [] && $legacy !== []) {
            return self::sortList($legacy);
        }

        $activeDefaults = array_values(array_filter(
            $defaults,
            static fn (string $item): bool => ! in_array($item, $excluded, true),
        ));

        return self::sortList(self::normalizeList(array_merge($activeDefaults, $extra)));
    }

    /**
     * @return array{excluded: list<string>, extra: list<string>}
     */
    public static function productConfigFromLegacyColor(?string $legacyColor, ?array $defaults = null): array
    {
        $defaults ??= self::defaults();
        $effective = self::parseList($legacyColor);

        if ($effective === []) {
            return ['excluded' => [], 'extra' => []];
        }

        $excluded = [];
        foreach ($defaults as $default) {
            if (! in_array($default, $effective, true)) {
                $excluded[] = $default;
            }
        }

        $extra = array_values(array_filter(
            $effective,
            static fn (string $item): bool => ! in_array($item, $defaults, true),
        ));

        return [
            'excluded' => self::normalizeList($excluded),
            'extra' => self::normalizeList($extra),
        ];
    }

    /**
     * @param  list<string>  $excluded
     * @param  list<string>  $extra
     * @return list<string>
     */
    public static function mergeConfig(array $excluded, array $extra, ?array $defaults = null): array
    {
        $defaults ??= self::defaults();
        $activeDefaults = array_values(array_filter(
            $defaults,
            static fn (string $item): bool => ! in_array($item, $excluded, true),
        ));

        return self::sortList(self::normalizeList(array_merge($activeDefaults, $extra)));
    }

    /**
     * @param  list<string>  $items
     * @param  array<string, string>  $map
     * @return list<string>
     */
    private static function renameInList(array $items, array $map): array
    {
        $out = [];
        foreach ($items as $item) {
            $out[] = $map[$item] ?? $item;
        }

        return self::normalizeList($out);
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private static function sortList(array $items): array
    {
        $sorted = self::normalizeList($items);
        if (class_exists(\Collator::class)) {
            $collator = new \Collator('ru_RU');
            usort($sorted, static fn (string $a, string $b): int => $collator->compare($a, $b));

            return $sorted;
        }

        sort($sorted, SORT_LOCALE_STRING);

        return $sorted;
    }

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
