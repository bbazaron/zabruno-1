<?php

namespace App\Support;

final class SchoolKey
{
    public static function forDirectory(int $id): string
    {
        return hash('sha256', 'id:'.$id);
    }

    public static function forCustom(string $city, string $name): string
    {
        return hash('sha256', 'custom:'.mb_strtolower(trim($city)).'|'.mb_strtolower(trim($name)));
    }
}
