<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Order $order,
    ) {
        $this->onConnection('database')->onQueue(env('ORDER_MAIL_QUEUE', 'mail'));
    }

    public function envelope(): Envelope
    {
        $kind = strtolower((string) $this->order->order_type) === 'ready_to_wear'
            ? 'готовая одежда'
            : 'пошив на заказ';

        return new Envelope(
            subject: 'Заказ №'.$this->order->id.' принят — '.$kind,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-received',
        );
    }
}
