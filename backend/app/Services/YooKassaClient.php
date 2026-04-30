<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Клиент REST API ЮKassa v3 (без отдельного SDK).
 *
 * @see https://yookassa.ru/developers/api#create_payment
 * @see https://yookassa.ru/developers/using-api/webhooks
 */
class YooKassaClient
{
    public function __construct(
        private ?string $shopId,
        private ?string $secretKey,
    ) {}

    public function isConfigured(): bool
    {
        return $this->shopId !== null && $this->shopId !== ''
            && $this->secretKey !== null && $this->secretKey !== '';
    }

    /**
     * @param  array<string, string>  $metadata
     * @return array{id: string, status: string, confirmation_url: string}
     */
    public function createRedirectPayment(
        string $amountValue,
        string $description,
        string $returnUrl,
        array $metadata = [],
        ?string $idempotenceKey = null,
    ): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ЮKassa не настроена: укажите YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
        }

        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->withHeaders([
                'Idempotence-Key' => $idempotenceKey ?: (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->timeout(45)
            ->post('https://api.yookassa.ru/v3/payments', [
                'amount' => [
                    'value' => $amountValue,
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'capture' => true,
                'description' => $description,
                'metadata' => $metadata,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Не удалось создать платёж в ЮKassa: HTTP '.$response->status()
            );
        }

        $data = $response->json();
        $id = data_get($data, 'id');
        $status = data_get($data, 'status');
        $url = data_get($data, 'confirmation.confirmation_url');

        if (! is_string($id) || ! is_string($url)) {
            throw new RuntimeException('ЮKassa вернула неполный ответ при создании платежа');
        }

        return [
            'id' => $id,
            'status' => is_string($status) ? $status : 'pending',
            'confirmation_url' => $url,
        ];
    }

    /**
     * Актуальное состояние платежа (для проверки вебхука).
     *
     * @return array<string, mixed>|null
     */
    public function getPayment(string $paymentId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->timeout(20)
            ->get('https://api.yookassa.ru/v3/payments/'.$paymentId);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Обработка тела HTTP-уведомления ЮKassa (notification).
     *
     * @param  array<string, mixed>  $payload
     * @return array{http: int, json: array<string, mixed>}
     */
    public function handleWebhook(array $payload): array
    {
        $event = (string) ($payload['event'] ?? '');
        $rawObject = $payload['object'] ?? null;
        $rawPaymentId = is_array($rawObject) ? (string) ($rawObject['id'] ?? '') : '';

        if (($payload['type'] ?? '') !== 'notification') {
            Log::warning('YooKassa webhook: invalid type', [
                'event' => $event,
                'payment_id' => $rawPaymentId,
                'result' => 'invalid_type',
            ]);
            return ['http' => 400, 'json' => ['message' => 'Invalid type']];
        }

        $object = $payload['object'] ?? null;
        if (! is_array($object) || ($object['id'] ?? '') === '') {
            Log::warning('YooKassa webhook: invalid object', [
                'event' => $event,
                'payment_id' => $rawPaymentId,
                'result' => 'invalid_object',
            ]);
            return ['http' => 400, 'json' => ['message' => 'Invalid object']];
        }

        if (! str_starts_with($event, 'payment.')) {
            Log::info('YooKassa webhook: skipped non payment event', [
                'event' => $event,
                'payment_id' => (string) $object['id'],
                'result' => 'skipped',
            ]);
            return ['http' => 200, 'json' => ['status' => 'skipped']];
        }

        $paymentId = (string) $object['id'];

        if (! $this->isConfigured()) {
            Log::error('YooKassa webhook: credentials not configured');

            return ['http' => 500, 'json' => ['message' => 'Server misconfiguration']];
        }

        $payment = $this->getPayment($paymentId);
        if ($payment === null) {
            Log::warning('YooKassa webhook: failed to fetch payment', ['payment_id' => $paymentId]);

            return ['http' => 502, 'json' => ['message' => 'Upstream error']];
        }

        $order = Order::query()->where('yookassa_payment_id', $paymentId)->first();
        if ($order === null) {
            Log::info('YooKassa webhook: no order for payment', [
                'event' => $event,
                'payment_id' => $paymentId,
                'order_id' => null,
                'status' => data_get($payment, 'status'),
                'result' => 'ignored_no_order',
            ]);

            return ['http' => 200, 'json' => ['status' => 'ignored']];
        }

        $metaOrderId = data_get($payment, 'metadata.order_id');
        if ((string) $metaOrderId !== (string) $order->id) {
            Log::warning('YooKassa webhook: metadata.order_id mismatch', [
                'event' => $event,
                'payment_id' => $paymentId,
                'order_id' => $order->id,
                'metadata' => $metaOrderId,
                'status' => data_get($payment, 'status'),
                'result' => 'ignored_meta_mismatch',
            ]);

            return ['http' => 200, 'json' => ['status' => 'ignored']];
        }

        $amountValue = data_get($payment, 'amount.value');
        $expected = round((float) $order->total_amount, 2);
        $actual = is_numeric($amountValue) ? round((float) $amountValue, 2) : null;
        if ($actual !== null && abs($actual - $expected) > 0.01) {
            Log::warning('YooKassa webhook: amount mismatch', [
                'event' => $event,
                'payment_id' => $paymentId,
                'order_id' => $order->id,
                'expected' => $expected,
                'actual' => $actual,
                'status' => data_get($payment, 'status'),
                'result' => 'ignored_amount_mismatch',
            ]);

            return ['http' => 200, 'json' => ['status' => 'ignored']];
        }

        $status = (string) (data_get($payment, 'status') ?? '');
        $updates = ['yookassa_payment_status' => $status];

        if ($event === 'payment.succeeded' && $status === 'succeeded') {
            $updates['status'] = 'confirmed';
        }
        if ($event === 'payment.canceled' || $status === 'canceled') {
            $updates['status'] = 'payment_cancelled';
        }

        $order->update($updates);

        Log::info('YooKassa webhook: order updated', [
            'event' => $event,
            'payment_id' => $paymentId,
            'order_id' => $order->id,
            'status' => $status,
            'result' => 'ok',
        ]);

        return ['http' => 200, 'json' => ['status' => 'ok']];
    }
}
