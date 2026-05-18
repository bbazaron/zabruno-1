<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Setting;

final class PickupAddress
{
    public const SETTING_KEY = 'pickup_address';

    public const DEFAULT = 'пгт. Агинское, ул. Цыбикова 6в, магазин Руно';

    public static function defaultAddress(): string
    {
        return self::normalize(
            Setting::getValue(self::SETTING_KEY, self::DEFAULT) ?? self::DEFAULT
        );
    }

    public static function forOrder(?Order $order): string
    {
        $stored = trim((string) ($order?->pickup_address ?? ''));
        if ($stored !== '') {
            return self::normalize($stored);
        }

        return self::defaultAddress();
    }

    public static function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $normalized !== '' ? $normalized : self::DEFAULT;
    }
}
