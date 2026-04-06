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
        $this->onQueue(env('RABBITMQ_MAIL_QUEUE', 'mail'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Заказ №'.$this->order->id.' принят',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-received',
        );
    }
}
