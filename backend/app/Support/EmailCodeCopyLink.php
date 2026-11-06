<?php

namespace App\Support;

class EmailCodeCopyLink
{
    public static function url(string $code): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return $base.'/email-code?code='.rawurlencode($code);
    }
}
