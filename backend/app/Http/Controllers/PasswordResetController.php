<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyPasswordResetCodeRequest;
use App\Services\PasswordResetService;

class PasswordResetController extends Controller
{
    public function __construct(
        private PasswordResetService $passwordReset,
    ) {}

    public function forgot(ForgotPasswordRequest $request)
    {
        $this->passwordReset->requestCode($request->validated('email'));

        return response()->json([
            'message' => 'Если такой аккаунт есть, мы отправим код на почту',
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
