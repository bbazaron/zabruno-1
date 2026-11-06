<?php

namespace App\Services;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetMailService
{
    public function queue(User $user, string $code): void
    {
        $email = trim((string) $user->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($email)->queue(new PasswordResetCode($user, $code));
        } catch (\Throwable $e) {
            Log::error('Не удалось поставить в очередь письмо с кодом восстановления пароля', [
                'user_id' => $user->id,
                'queue_connection' => 'database',
                'exception' => $e,
            ]);
            report($e);
        }
    }
}
