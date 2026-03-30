<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignUpRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function register(SignUpRequest $request)
    {
        $result = $this->userService->register($request);

        return response()->json($result['data'], $result['status']);
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
