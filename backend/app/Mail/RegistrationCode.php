<?php

namespace App\Mail;

use App\Services\RegistrationCodeService;
use App\Support\EmailCodeCopyLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RegistrationCode extends Mailable
{
    public function __construct(
        public string $email,
        public string $name,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Код для подтверждения почты',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.registration-code',
            with: [
                'ttlMinutes' => RegistrationCodeService::CODE_TTL_MINUTES,
                'copyUrl' => EmailCodeCopyLink::url($this->code),
            ],
        );
    }
}
