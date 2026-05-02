<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderReceivedMailService;
use App\Services\YooKassaClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReconcilePendingYooKassaPayments
{
    public function __construct(
        private readonly int $hoursBack = 24,
    ) {}

    public function handle(YooKassaClient $yooKassa, OrderReceivedMailService $orderReceivedMail): void
    {
        $from = Carbon::now()->subHours($this->hoursBack);

        Order::query()
            ->where('status', 'pending_payment')
            ->whereNotNull('yookassa_payment_id')
            ->where('created_at', '>=', $from)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($yooKassa, $orderReceivedMail): void {
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

                    if ($status === 'succeeded') {
                        $claimed = Order::query()
                            ->whereKey($order->id)
                            ->where('yookassa_payment_id', $paymentId)
                            ->where('status', 'pending_payment')
                            ->whereNull('order_received_email_sent_at')
                            ->update([
                                'status' => 'confirmed',
                                'yookassa_payment_status' => $status,
                                'order_received_email_sent_at' => now(),
                            ]);

                        if ($claimed === 1) {
                            $orderReceivedMail->queueAfterCommit($order->id, true);
                        } else {
                            Order::query()->whereKey($order->id)->update([
                                'yookassa_payment_status' => $status,
                            ]);
                        }
                    } elseif ($status === 'canceled') {
                        Order::query()
                            ->whereKey($order->id)
                            ->where('yookassa_payment_id', $paymentId)
                            ->where('status', 'pending_payment')
                            ->update([
                                'yookassa_payment_status' => $status,
                                'status' => 'payment_cancelled',
                            ]);
                    } else {
                        Order::query()->whereKey($order->id)->update([
                            'yookassa_payment_status' => $status,
                        ]);
                    }
                }
            });
    }
}
