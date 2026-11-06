<?php

namespace App\Services;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetMailService
{
    public function send(User $user, string $code): void
    {
        $email = trim((string) $user->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('У пользователя некорректный email');
        }

        $registered = User::query()
            ->whereKey($user->id)
            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email)])
            ->exists();
        if (! $registered) {
            throw new \RuntimeException('Аккаунт с такой почтой не найден');
        }

        try {
            Mail::to($email)->send(new PasswordResetCode($user, $code));
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить письмо с кодом восстановления пароля', [
                'user_id' => $user->id,
                'exception' => $e,
            ]);
            report($e);

            throw $e;
        }
    }
}
