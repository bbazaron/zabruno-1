<?php

namespace App\Support;

final class OrderSchoolSummary
{
    /**
     * @param  list<array{school_city?: string|null, school_name?: string|null}>  $lines
     * @return array{settlement: string, school: string}
     */
    public static function fromLines(array $lines): array
    {
        $pairs = [];
        foreach ($lines as $line) {
            $city = trim((string) ($line['school_city'] ?? ''));
            $name = trim((string) ($line['school_name'] ?? ''));
            if ($city === '' && $name === '') {
                continue;
            }
            $pairs[mb_strtolower($city).'|'.mb_strtolower($name)] = [
                'city' => $city,
                'name' => $name,
            ];
        }

        $cities = [];
        $nameCities = [];
        foreach ($pairs as $pair) {
            if ($pair['city'] !== '' && ! in_array($pair['city'], $cities, true)) {
                $cities[] = $pair['city'];
            }
            if ($pair['name'] === '') {
                continue;
            }
            $nameCities[$pair['name']][] = $pair['city'];
        }

        $labels = [];
        foreach ($pairs as $pair) {
            if ($pair['name'] === '') {
                continue;
            }
            $citiesForName = array_values(array_unique($nameCities[$pair['name']] ?? []));
            $label = count($citiesForName) > 1 && $pair['city'] !== ''
                ? $pair['name'].' ('.$pair['city'].')'
                : $pair['name'];
            if (! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return [
            'settlement' => $cities === [] ? 'Не указано' : self::clip(implode(', ', $cities)),
            'school' => $labels === [] ? 'Не указано' : self::clip(implode(', ', $labels)),
        ];
    }

    private static function clip(string $value): string
    {
        if (mb_strlen($value) <= 255) {
            return $value;
        }

        return mb_substr($value, 0, 254).'…';
    }
}
