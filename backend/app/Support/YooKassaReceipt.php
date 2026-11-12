<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

final class YooKassaReceipt
{
    /**
     * Объект receipt для API ЮKassa (54-ФЗ).
     *
     * @return array<string, mixed>
     */
    public static function fromOrder(Order $order, ?string $amountOverride = null): array
    {
        $order->loadMissing('items');

        $customer = self::customerFromOrder($order);
        $items = self::itemsFromOrder($order, $amountOverride);

        $receipt = [
            'customer' => $customer,
            'items' => $items,
        ];

        $taxSystemCode = config('services.yookassa.receipt.tax_system_code');
        if ($taxSystemCode !== null && $taxSystemCode !== '') {
            $receipt['tax_system_code'] = (int) $taxSystemCode;
        }

        return $receipt;
    }

    /**
     * @return array{email?: string, phone?: string}
     */
    private static function customerFromOrder(Order $order): array
    {
        $customer = [];

        $email = trim((string) ($order->parent_email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $customer['email'] = $email;
        }

        $phone = self::normalizePhone((string) ($order->parent_phone ?? ''));
        if ($phone !== null) {
            $customer['phone'] = $phone;
        }

        if ($customer === []) {
            throw new \RuntimeException(
                'Для чека ЮKassa нужен email или телефон покупателя'
            );
        }

        return $customer;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function itemsFromOrder(Order $order, ?string $amountOverride = null): array
    {
        $vatCode = (int) config('services.yookassa.receipt.vat_code', 1);
        $paymentMode = (string) config('services.yookassa.receipt.payment_mode', 'full_payment');
        $paymentSubject = (string) config('services.yookassa.receipt.payment_subject', 'commodity');

        $targetTotal = round((float) ($amountOverride ?? $order->total_amount), 2);
        if ($targetTotal <= 0) {
            throw new \RuntimeException('Сумма для чека ЮKassa должна быть больше нуля');
        }

        $lines = [];
        $sum = 0.0;

        foreach ($order->items as $item) {
            if (! $item instanceof OrderItem) {
                continue;
            }

            $qty = max(1, (int) $item->quantity);
            $unitPrice = self::resolveUnitPrice($item);
            if ($unitPrice === null) {
                continue;
            }

            $sum += round($unitPrice * $qty, 2);

            $description = trim((string) $item->product_name);
            if ($description === '') {
                $description = 'Товар';
            }

            $lines[] = self::receiptLine(
                $description,
                $qty,
                $unitPrice,
                $vatCode,
                $paymentMode,
                $paymentSubject,
            );
        }

        if ($lines === []) {
            return [
                self::receiptLine(
                    'Заказ №'.$order->id,
                    1,
                    $targetTotal,
                    $vatCode,
                    $paymentMode,
                    $paymentSubject,
                ),
            ];
        }

        $sum = round($sum, 2);
        $diff = round($targetTotal - $sum, 2);
        if (abs($diff) >= 0.01) {
            $lines = self::applyTotalDiff(
                $lines,
                $diff,
                $targetTotal,
                $order,
                $vatCode,
                $paymentMode,
                $paymentSubject,
            );
        }

        return $lines;
    }

    /**
     * В чеке ЮKassa amount — цена одной единицы. Касса считает quantity × amount
     * и сверяет итог с суммой платежа.
     *
     * @return array<string, mixed>
     */
    private static function receiptLine(
        string $description,
        int|float $quantity,
        float $unitPrice,
        int $vatCode,
        string $paymentMode,
        string $paymentSubject,
    ): array {
        return [
            'description' => mb_substr($description, 0, 128),
            'quantity' => number_format((float) $quantity, 2, '.', ''),
            'amount' => [
                'value' => number_format(round($unitPrice, 2), 2, '.', ''),
                'currency' => 'RUB',
            ],
            'vat_code' => $vatCode,
            'payment_mode' => $paymentMode,
            'payment_subject' => $paymentSubject,
        ];
    }

    /**
     * Разницу между суммой заказа и суммой позиций нельзя прибавлять к цене единицы:
     * при quantity > 1 касса умножит её повторно и отклонит платёж.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private static function applyTotalDiff(
        array $lines,
        float $diff,
        float $targetTotal,
        Order $order,
        int $vatCode,
        string $paymentMode,
        string $paymentSubject,
    ): array {
        $fallback = [
            self::receiptLine(
                'Заказ №'.$order->id,
                1,
                $targetTotal,
                $vatCode,
                $paymentMode,
                $paymentSubject,
            ),
        ];

        $lastIndex = count($lines) - 1;
        $last = $lines[$lastIndex];
        $lastQty = (int) round((float) $last['quantity']);
        $lastUnit = (float) $last['amount']['value'];

        if ($lastQty <= 1) {
            $adjusted = round($lastUnit + $diff, 2);
            if ($adjusted <= 0) {
                return $fallback;
            }

            $lines[$lastIndex]['amount']['value'] = number_format($adjusted, 2, '.', '');

            return $lines;
        }

        $adjustedUnit = round($lastUnit + $diff, 2);
        if ($adjustedUnit <= 0) {
            return $fallback;
        }

        $lines[$lastIndex]['quantity'] = number_format($lastQty - 1, 2, '.', '');
        $extra = $last;
        $extra['quantity'] = '1.00';
        $extra['amount']['value'] = number_format($adjustedUnit, 2, '.', '');
        $lines[] = $extra;

        return $lines;
    }

    private static function resolveUnitPrice(OrderItem $item): ?float
    {
        if ($item->product_id) {
            $product = Product::query()->find($item->product_id);
            if ($product !== null) {
                return round((float) $product->price, 2);
            }
        }

        $name = trim((string) $item->product_name);
        if ($name === '') {
            return null;
        }

        $product = Product::query()->where('name', $name)->first();
        if ($product === null) {
            return null;
        }

        return round((float) $product->price, 2);
    }

    private static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
