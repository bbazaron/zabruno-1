<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Постановка письма «заказ принят» в очередь БД ({@see OrderReceived}).
 *
 * Поле {@see Order::$order_received_email_sent_at} выставляется при успешной атомарной
 * фиксации слота в ЮKassa (webhook/reconcile) или здесь для заказов без оплаты.
 */
class OrderReceivedMailService
{
    /**
     * После коммита транзакции загружает заказ с позициями и ставит письмо в очередь.
     *
     * @param  bool  $mailSlotReservedByCaller  если true — колонка order_received_email_sent_at уже выставлена атомарным UPDATE при переходе в confirmed
     */
    public function queueAfterCommit(int $orderId, bool $mailSlotReservedByCaller = false): void
    {
        DB::afterCommit(function () use ($orderId, $mailSlotReservedByCaller): void {
            $fresh = Order::query()->with(['items', 'user'])->find($orderId);
            if ($fresh === null) {
                Log::warning('Письмо о заказе не поставлено в очередь: заказ не найден после коммита', [
                    'order_id' => $orderId,
                ]);

                return;
            }

            $email = trim((string) $fresh->parent_email);
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            if (! $mailSlotReservedByCaller) {
                $claimed = Order::query()
                    ->whereKey($orderId)
                    ->whereNull('order_received_email_sent_at')
                    ->update(['order_received_email_sent_at' => now()]);

                if ($claimed !== 1) {
                    return;
                }
            }

            try {
                Mail::to($email)->queue(new OrderReceived($fresh));
            } catch (\Throwable $e) {
                Log::error('Не удалось поставить в очередь БД письмо о принятом заказе', [
                    'order_id' => $orderId,
                    'queue_connection' => 'database',
                    'exception' => $e,
                ]);
                report($e);

                try {
                    error_log(sprintf(
                        'OrderReceived queue failed (order_id=%s, connection=database): %s',
                        (string) $orderId,
                        $e->getMessage()
                    ));
                } catch (\Throwable) {
                }
            }
        });
    }
}
