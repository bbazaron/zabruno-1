<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentRefund;
use App\Support\YooKassaReceipt;
use Illuminate\Support\Facades\DB;
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
        private OrderReceivedMailService $orderReceivedMail,
    ) {}

    public function isConfigured(): bool
    {
        return $this->shopId !== null && $this->shopId !== ''
            && $this->secretKey !== null && $this->secretKey !== '';
    }

    /**
     * @param  array<string, string>  $metadata
     * @param  array<string, mixed>|null  $receipt
     * @return array{id: string, status: string, confirmation_url: string}
     */
    public function createRedirectPayment(
        string $amountValue,
        string $description,
        string $returnUrl,
        array $metadata = [],
        ?string $idempotenceKey = null,
        ?array $receipt = null,
    ): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ЮKassa не настроена: укажите YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
        }

        $payload = [
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
        ];

        if ($receipt !== null) {
            $payload['receipt'] = $receipt;
        }

        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->withHeaders([
                'Idempotence-Key' => $idempotenceKey ?: (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->timeout(45)
            ->post('https://api.yookassa.ru/v3/payments', $payload);

        if (! $response->successful()) {
            Log::warning('YooKassa create payment failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            $descriptionFromApi = data_get($response->json(), 'description');
            $suffix = is_string($descriptionFromApi) && $descriptionFromApi !== ''
                ? ': '.$descriptionFromApi
                : '';
            throw new RuntimeException(
                'Не удалось создать платёж в ЮKassa: HTTP '.$response->status().$suffix
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
     * @param  array<string, string>  $metadata
     * @param  array<string, mixed>|null  $receipt
     * @return array{id:string,status:string,payment_id:string,amount:string}
     */
    public function createRefund(
        string $paymentId,
        string $amountValue,
        string $description,
        ?string $idempotenceKey = null,
        array $metadata = [],
        ?array $receipt = null,
    ): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ЮKassa не настроена: укажите YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env');
        }

        $payload = [
            'payment_id' => $paymentId,
            'amount' => [
                'value' => $amountValue,
                'currency' => 'RUB',
            ],
            'description' => $description,
            'metadata' => $metadata,
        ];

        if ($receipt !== null) {
            $payload['receipt'] = $receipt;
        }

        $response = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->withHeaders([
                'Idempotence-Key' => $idempotenceKey ?: (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->timeout(45)
            ->post('https://api.yookassa.ru/v3/refunds', $payload);

        if (! $response->successful()) {
            Log::warning('YooKassa create refund failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            $descriptionFromApi = data_get($response->json(), 'description');
            $suffix = is_string($descriptionFromApi) && $descriptionFromApi !== ''
                ? ': '.$descriptionFromApi
                : '';
            throw new RuntimeException('Не удалось создать возврат в ЮKassa: HTTP '.$response->status().$suffix);
        }

        $data = $response->json();
        $id = data_get($data, 'id');
        $status = data_get($data, 'status');
        $responsePaymentId = data_get($data, 'payment_id');
        $responseAmount = data_get($data, 'amount.value');

        if (! is_string($id) || ! is_string($responsePaymentId) || ! is_string($responseAmount)) {
            throw new RuntimeException('ЮKassa вернула неполный ответ при создании возврата');
        }

        return [
            'id' => $id,
            'status' => is_string($status) ? $status : 'pending',
            'payment_id' => $responsePaymentId,
            'amount' => $responseAmount,
        ];
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

        return $this->settleFetchedPayment($payment, $event);
    }

    /**
     * Применяет уже проверенный в ЮKassa платёж к заказу.
     * Повторная успешная оплата другого платежа по тому же заказу возвращается.
     *
     * @param  array<string, mixed>  $payment
     * @return array{http: int, json: array<string, mixed>}
     */
    public function settleFetchedPayment(array $payment, string $event): array
    {
        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '') {
            return ['http' => 400, 'json' => ['message' => 'Invalid object']];
        }

        $attempt = OrderPayment::query()->where('yookassa_payment_id', $paymentId)->first();
        $order = $attempt?->order ?? Order::query()->where('yookassa_payment_id', $paymentId)->first();
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
        $confirmationUrl = data_get($payment, 'confirmation.confirmation_url');
        $duplicatePaymentId = null;

        $result = DB::transaction(function () use ($order, $paymentId, $status, $event, $confirmationUrl, $actual, &$duplicatePaymentId) {
            $locked = Order::query()->lockForUpdate()->find($order->id);
            if ($locked === null) {
                return ['http' => 200, 'json' => ['status' => 'ignored']];
            }

            $this->rememberPaymentAttempt(
                $locked,
                $paymentId,
                $status,
                is_string($confirmationUrl) ? $confirmationUrl : null,
                is_numeric($actual) ? number_format((float) $actual, 2, '.', '') : number_format((float) $locked->total_amount, 2, '.', ''),
            );

            $isCurrent = (string) $locked->yookassa_payment_id === $paymentId;
            $paidStatuses = ['confirmed', 'processing', 'production', 'completed', 'partially_refunded', 'refunded'];
            $awaitingPayment = ['pending_payment', 'payment_cancelled'];

            if ($event === 'payment.succeeded' && $status === 'succeeded') {
                $alreadyCaptured = in_array((string) $locked->status, $paidStatuses, true)
                    && (string) $locked->yookassa_payment_status === 'succeeded'
                    && (string) $locked->yookassa_payment_id !== ''
                    && (string) $locked->yookassa_payment_id !== $paymentId;

                if ($alreadyCaptured) {
                    $duplicatePaymentId = $paymentId;

                    return ['http' => 200, 'json' => ['status' => 'duplicate_refunded']];
                }

                if (in_array((string) $locked->status, $awaitingPayment, true)) {
                    $claimed = Order::query()
                        ->whereKey($locked->id)
                        ->whereIn('status', $awaitingPayment)
                        ->whereNull('order_received_email_sent_at')
                        ->update([
                            'status' => 'confirmed',
                            'yookassa_payment_id' => $paymentId,
                            'yookassa_payment_status' => $status,
                            'order_received_email_sent_at' => now(),
                        ]);

                    if ($claimed === 1) {
                        $this->orderReceivedMail->queueAfterCommit($locked->id, true);
                    } else {
                        Order::query()
                            ->whereKey($locked->id)
                            ->whereIn('status', $awaitingPayment)
                            ->update([
                                'status' => 'confirmed',
                                'yookassa_payment_id' => $paymentId,
                                'yookassa_payment_status' => $status,
                            ]);
                    }
                } elseif ($isCurrent) {
                    Order::query()->whereKey($locked->id)->update([
                        'yookassa_payment_status' => $status,
                    ]);
                }
            } elseif (($event === 'payment.canceled' || $status === 'canceled') && $isCurrent && $locked->status === 'pending_payment') {
                Order::query()
                    ->whereKey($locked->id)
                    ->where('yookassa_payment_id', $paymentId)
                    ->where('status', 'pending_payment')
                    ->update([
                        'yookassa_payment_status' => $status !== '' ? $status : 'canceled',
                        'status' => 'payment_cancelled',
                    ]);
            } elseif ($isCurrent && $status !== '') {
                Order::query()
                    ->whereKey($locked->id)
                    ->where('yookassa_payment_id', $paymentId)
                    ->update(['yookassa_payment_status' => $status]);
            }

            Log::info('YooKassa webhook: order updated', [
                'event' => $event,
                'payment_id' => $paymentId,
                'order_id' => $locked->id,
                'status' => $status,
                'result' => 'ok',
            ]);

            return ['http' => 200, 'json' => ['status' => 'ok']];
        });

        if (is_string($duplicatePaymentId) && $duplicatePaymentId !== '') {
            try {
                $this->refundDuplicatePayment($order->fresh() ?? $order, $duplicatePaymentId, (string) $amountValue);
            } catch (\Throwable $e) {
                Log::error('YooKassa duplicate payment refund failed', [
                    'order_id' => $order->id,
                    'payment_id' => $duplicatePaymentId,
                    'exception' => $e,
                ]);

                return ['http' => 500, 'json' => ['message' => 'Duplicate refund failed']];
            }

            Log::warning('YooKassa duplicate payment refunded', [
                'order_id' => $order->id,
                'payment_id' => $duplicatePaymentId,
                'kept_payment_id' => $order->yookassa_payment_id,
            ]);
        }

        return $result;
    }

    public function rememberPaymentAttempt(
        Order $order,
        string $paymentId,
        ?string $status,
        ?string $confirmationUrl,
        string $amount,
        ?string $idempotenceKey = null,
    ): void {
        $existing = OrderPayment::query()->where('yookassa_payment_id', $paymentId)->first();
        if ($existing === null && $idempotenceKey !== null) {
            $existing = OrderPayment::query()
                ->where('order_id', $order->id)
                ->where('idempotence_key', $idempotenceKey)
                ->first();
        }

        $payload = [
            'order_id' => $order->id,
            'yookassa_payment_id' => $paymentId,
            'amount' => $amount,
            'status' => $status,
        ];
        if (is_string($confirmationUrl) && $confirmationUrl !== '') {
            $payload['confirmation_url'] = $confirmationUrl;
        }
        if ($idempotenceKey !== null && $idempotenceKey !== '') {
            $payload['idempotence_key'] = $idempotenceKey;
        }

        if ($existing === null) {
            if (! isset($payload['idempotence_key'])) {
                $payload['idempotence_key'] = 'payment:'.$paymentId;
            }
            OrderPayment::query()->create($payload);

            return;
        }

        $existing->update($payload);
    }

    /**
     * Возврат лишнего платежа, если заказ уже оплачен другим платежом.
     * Сумма возврата не уменьшает refunded_amount заказа: это не возврат товара.
     */
    public function refundDuplicatePayment(Order $order, string $paymentId, string $amountValue): void
    {
        $amount = number_format(round((float) $amountValue, 2), 2, '.', '');
        if ((float) $amount <= 0) {
            return;
        }

        $idempotenceKey = 'dup-refund:'.$paymentId;
        $refund = PaymentRefund::query()->where('idempotence_key', $idempotenceKey)->first();
        if ($refund !== null && $refund->status !== 'failed' && $refund->yookassa_refund_id) {
            return;
        }

        if ($refund === null) {
            $refund = PaymentRefund::query()->create([
                'order_id' => $order->id,
                'created_by' => (int) ($order->user_id ?? 0),
                'yookassa_payment_id' => $paymentId,
                'amount' => $amount,
                'reason_code' => 'duplicate_payment',
                'reason_comment' => 'Повторная оплата того же заказа',
                'status' => 'pending',
                'idempotence_key' => $idempotenceKey,
            ]);
        }

        $gatewayRefund = $this->createRefund(
            $paymentId,
            $amount,
            'Возврат повторной оплаты заказа №'.$order->id,
            $idempotenceKey,
            [
                'order_id' => (string) $order->id,
                'reason_code' => 'duplicate_payment',
            ],
            YooKassaReceipt::fromOrder($order->loadMissing('items'), $amount),
        );

        $refund->update([
            'yookassa_refund_id' => $gatewayRefund['id'],
            'status' => $gatewayRefund['status'],
            'gateway_response' => $gatewayRefund,
        ]);
    }
}
