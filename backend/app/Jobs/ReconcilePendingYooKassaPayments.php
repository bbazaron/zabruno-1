<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\YooKassaClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReconcilePendingYooKassaPayments
{
    public function __construct(
        private readonly int $hoursBack = 24,
    ) {}

    public function handle(YooKassaClient $yooKassa): void
    {
        $from = Carbon::now()->subHours($this->hoursBack);

        Order::query()
            ->where('status', 'pending_payment')
            ->whereNotNull('yookassa_payment_id')
            ->where('created_at', '>=', $from)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($yooKassa): void {
                foreach ($orders as $order) {
                    $paymentId = (string) $order->yookassa_payment_id;
                    if ($paymentId === '') {
                        continue;
                    }

                    $payment = $yooKassa->getPayment($paymentId);
                    if ($payment === null) {
                        Log::warning('YooKassa reconcile: failed to fetch payment', [
                            'order_id' => $order->id,
                            'payment_id' => $paymentId,
                        ]);
                        continue;
                    }

                    $status = (string) (data_get($payment, 'status') ?? '');
                    $updates = ['yookassa_payment_status' => $status];
                    if ($status === 'succeeded') {
                        $updates['status'] = 'confirmed';
                    } elseif ($status === 'canceled') {
                        $updates['status'] = 'payment_cancelled';
                    }

                    $order->update($updates);
                }
            });
    }
}
