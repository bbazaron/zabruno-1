<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
        /** Публичный URL фронтенда (куда вернётся покупатель после оплаты), например http://localhost:5173 */
        'frontend_url' => rtrim((string) env('FRONTEND_URL', ''), '/'),
        /**
         * Чек 54-ФЗ (обязателен, если в кабинете ЮKassa включена фискализация).
         * vat_code: 1 без НДС, 2 НДС 0%, 3 НДС 10%, 4 НДС 20%, …
         * @see https://yookassa.ru/developers/payment-acceptance/receipts/54fz/other-services/parameters-values
         */
        'receipt' => [
            'vat_code' => (int) env('YOOKASSA_RECEIPT_VAT_CODE', 1),
            'payment_mode' => env('YOOKASSA_RECEIPT_PAYMENT_MODE', 'full_payment'),
            'payment_subject' => env('YOOKASSA_RECEIPT_PAYMENT_SUBJECT', 'commodity'),
            'tax_system_code' => env('YOOKASSA_RECEIPT_TAX_SYSTEM_CODE'),
        ],
    ],

];
