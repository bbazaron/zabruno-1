<?php

namespace App\Support;

use App\Models\Setting;

final class HomeOrderButtons
{
    public const CUSTOM_SIZE_KEY = 'home_button_custom_size_visible';

    public const CATALOG_ORDER_KEY = 'home_button_catalog_order_visible';

    public const LEGACY_KEY = 'home_order_buttons_visible';

    /**
     * @return array{custom_size: bool, catalog_order: bool}
     */
    public static function state(): array
    {
        return [
            'custom_size' => self::isOn(self::CUSTOM_SIZE_KEY),
            'catalog_order' => self::isOn(self::CATALOG_ORDER_KEY),
        ];
    }

    public static function setState(bool $customSize, bool $catalogOrder): void
    {
        Setting::setValue(self::CUSTOM_SIZE_KEY, $customSize ? '1' : '0');
        Setting::setValue(self::CATALOG_ORDER_KEY, $catalogOrder ? '1' : '0');
    }

    private static function isOn(string $key): bool
    {
        $value = Setting::getValue($key);
        if ($value === null) {
            $value = Setting::getValue(self::LEGACY_KEY, '1');
        }

        return ! in_array(strtolower((string) $value), ['0', 'false', 'no'], true);
    }
}
