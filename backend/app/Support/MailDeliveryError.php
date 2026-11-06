<?php

namespace App\Support;

class MailDeliveryError
{
    public static function forUser(\Throwable $e): string
    {
        $text = $e->getMessage();
        if (preg_match('/553|5\.1\.10|free_tier|no valid recipients|expected response code/i', $text) === 1) {
            return 'Не удалось отправить код на эту почту. Попробуйте другой адрес.';
        }

        return $text !== '' ? $text : 'Не удалось отправить код. Попробуйте снова.';
    }
}
