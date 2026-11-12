<?php

namespace App\Support;

final class SchoolColorLabel
{
    /**
     * @return array{school: string, color: string, split: bool}
     */
    public static function split(string $value): array
    {
        $value = trim($value);
        $pos = mb_strpos($value, ',');
        if ($pos === false) {
            return ['school' => '', 'color' => $value, 'split' => false];
        }

        $school = trim(mb_substr($value, 0, $pos));
        $color = trim(mb_substr($value, $pos + 1));
        if ($school === '') {
            return ['school' => '', 'color' => $value, 'split' => false];
        }

        return ['school' => $school, 'color' => $color, 'split' => true];
    }
}
