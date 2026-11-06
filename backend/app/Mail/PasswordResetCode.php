<?php

namespace App\Mail;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetCode extends Mailable
{
    public function __construct(
        public User $user,
        public string $code,
    ) {}

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
            with: [
                'ttlMinutes' => PasswordResetService::CODE_TTL_MINUTES,
            ],
        );
    }
}
