<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignUpRequest;
use App\Services\DTO\LoginDTO;
use App\Services\DTO\SignUpDTO;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function register(SignUpRequest $request)
    {
        $dto = SignUpDTO::fromRequest($request);
        $user = $this->userService->signUp($dto);
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Регистрация успешна',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $dto = LoginDTO::fromRequest($request);
        $user = $this->userService->login($dto);
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Вход выполнен',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user !== null) {
            $this->userService->logout($user);
        }

        return response()->json([
            'message' => 'Вы вышли из аккаунта',
        ]);
    }
}
