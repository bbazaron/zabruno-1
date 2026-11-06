<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyPasswordResetCodeRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use App\Support\MailDeliveryError;

class PasswordResetController extends Controller
{
    public function __construct(
        private PasswordResetService $passwordReset,
    ) {}

    public function forgot(ForgotPasswordRequest $request)
    {
        $email = mb_strtolower(trim((string) $request->validated('email')));
        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($user === null) {
            return response()->json([
                'message' => 'Аккаунт с такой почтой не найден',
            ], 422);
        }

        try {
            $retryAfter = $this->passwordReset->requestCode($user->email);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => MailDeliveryError::forUser($e),
            ], 422);
        }

        if ($retryAfter !== null) {
            return response()->json([
                'message' => 'Повторная отправка будет доступна через '.$retryAfter.' с',
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response()->json([
            'message' => 'Код отправлен на почту',
        ]);
    }

    public function verify(VerifyPasswordResetCodeRequest $request)
    {
        $ok = $this->passwordReset->verifyCode(
            $request->validated('email'),
            $request->validated('code'),
        );

        if (! $ok) {
            return response()->json([
                'message' => 'Неверный или просроченный код',
            ], 422);
        }

        return response()->json([
            'message' => 'Код подтверждён',
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $ok = $this->passwordReset->resetPassword(
            $request->validated('email'),
            $request->validated('code'),
            $request->validated('password'),
        );

        if (! $ok) {
            return response()->json([
                'message' => 'Неверный или просроченный код',
            ], 422);
        }

        return response()->json([
            'message' => 'Пароль обновлён. Войдите с новым паролем',
        ]);
    }
}
