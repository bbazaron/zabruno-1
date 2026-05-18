<?php

namespace App\Support;

final class ProductSizes
{
    /** @var list<string> */
    public const DEFAULT_SIZES = ['28', '30', '32', '34', '36', '38', '40', '42', '44'];

    /**
     * @return list<string>
     */
    public static function defaultList(): array
    {
        return self::DEFAULT_SIZES;
    }

    /**
   * @param  mixed  $raw
   * @return list<string>
   */
  public static function normalizeList(mixed $raw): array
  {
    if (is_string($raw)) {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $raw = $decoded;
      } else {
        $raw = preg_split('/[,;|\n]+/u', $raw) ?: [];
      }
    }

    if (! is_array($raw)) {
      return [];
    }

    $out = [];
    foreach ($raw as $item) {
      $normalized = self::normalizeOne($item);
      if ($normalized === null) {
        continue;
      }
      if (! in_array($normalized, $out, true)) {
        $out[] = $normalized;
      }
    }

    usort($out, [self::class, 'compareNumeric']);

    return $out;
  }

  public static function normalizeOne(mixed $value): ?string
  {
    $raw = trim((string) $value);
    if ($raw === '') {
      return null;
    }

    $raw = str_replace(',', '.', $raw);
    if (! is_numeric($raw)) {
      return null;
    }

    $number = (float) $raw;
    if ($number <= 0 || $number > 999) {
      return null;
    }

    if (floor($number) === $number) {
      return (string) (int) $number;
    }

    $formatted = rtrim(rtrim(sprintf('%.2F', $number), '0'), '.');

    return $formatted === '' ? null : $formatted;
  }

  public static function compareNumeric(string $a, string $b): int
  {
    return (float) $a <=> (float) $b;
  }

  public static function isAllowed(?string $size, array $allowed): bool
  {
    $normalized = self::normalizeOne($size);
    if ($normalized === null) {
      return false;
    }

    foreach ($allowed as $item) {
      if ($item === $normalized) {
        return true;
      }
    }

    return false;
  }
}
