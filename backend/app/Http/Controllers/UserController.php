<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\ConfirmRegistrationRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResendRegistrationCodeRequest;
use App\Http\Requests\SignUpRequest;
use App\Services\RegistrationCodeService;
use App\Services\UserService;
use App\Support\MailDeliveryError;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private RegistrationCodeService $registrationCodes,
    ) {}

    public function register(SignUpRequest $request)
    {
        try {
            $retryAfter = $this->registrationCodes->requestCode(
                (string) $request->validated('firstName'),
                (string) $request->validated('lastName'),
                (string) $request->validated('email'),
            );
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

    public function confirmRegistration(ConfirmRegistrationRequest $request)
    {
        try {
            $registrationToken = $this->registrationCodes->verify(
                (string) $request->validated('email'),
                (string) $request->validated('code'),
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => MailDeliveryError::forUser($e),
            ], 422);
        }

        return response()->json([
            'message' => 'Почта подтверждена',
            'registration_token' => $registrationToken,
        ]);
    }

    public function completeRegistration(CompleteRegistrationRequest $request)
    {
        try {
            $user = $this->registrationCodes->complete(
                (string) $request->validated('email'),
                (string) $request->validated('registration_token'),
                (string) $request->validated('password'),
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => MailDeliveryError::forUser($e),
            ], 422);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Регистрация успешна',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function resendRegistrationCode(ResendRegistrationCodeRequest $request)
    {
        try {
            $retryAfter = $this->registrationCodes->resend((string) $request->validated('email'));
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

    public function login(LoginRequest $request)
    {
        $result = $this->userService->loginAction($request);

        return response()->json($result['data'], $result['status']);
    }

    public function logout(Request $request)
    {
        $result = $this->userService->logoutAction($request);

        return response()->json($result['data'], $result['status']);
    }

    public function getUser(Request $request)
    {
        $result = $this->userService->getUserAction($request);

        return response()->json($result['data'], $result['status']);
    }

    public function updateProfile(Request $request)
    {
        $result = $this->userService->updateProfileAction($request);

        return response()->json($result['data'], $result['status']);
    }

    public function getAdmins(Request $request)
    {
        $result = $this->userService->getAdminsAction($request);

        return response()->json($result['data'], $result['status']);
    }

    public function createAdmin(Request $request)
    {
        $result = $this->userService->createAdminAction($request);

        return response()->json($result['data'], $result['status']);
    }
}
