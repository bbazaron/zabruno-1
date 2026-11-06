<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public User $user,
        public string $code,
    ) {
        $this->onConnection('database')->onQueue(env('ORDER_MAIL_QUEUE', 'mail'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Код для восстановления пароля',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-reset-code',
        );
    }
}
