<?php

namespace App\Services;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use App\Services\DTO\LoginDTO;
use App\Services\DTO\SignUpDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function register(SignUpRequest $request): array
    {
        $dto = SignUpDTO::fromRequest($request);
        $user = $this->signUp($dto);
        $token = $user->createToken('auth')->plainTextToken;

        return [
            'status' => 200,
            'data' => [
                'message' => 'Регистрация успешна',
                'token' => $token,
                'user' => $user,
            ],
        ];
    }

    public function signUp(SignUpDTO $dto): User
    {
        return User::create([
            'name' => $dto->getFirstName().' '.$dto->getLastName(),
            'email' => $dto->getEmail(),
            'password' => $dto->getPassword(),
            'role' => 'user',
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function loginAction(LoginRequest $request): array
    {
        $dto = LoginDTO::fromRequest($request);
        $user = $this->login($dto);
        $token = $user->createToken('auth')->plainTextToken;

        return [
            'status' => 200,
            'data' => [
                'message' => 'Вход выполнен',
                'token' => $token,
                'user' => $user,
            ],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function login(LoginDTO $dto): User
    {
        $user = User::where('email', $dto->getEmail())->first();

        if (! $user || ! Hash::check($dto->getPassword(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        return $user;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function logoutAction(Request $request): array
    {
        $user = $request->user();
        if ($user !== null) {
            $this->logout($user);
        }

        return [
            'status' => 200,
            'data' => [
                'message' => 'Вы вышли из аккаунта',
            ],
        ];
    }

    /**
     * Возвращает данные текущего пользователя для фронта.
     *
     * @return array{name: string, mail: string, role: string}|null
     */
    public function getUser(int $userId): ?array
    {
        $user = User::query()
            ->select('name', 'email', 'role')
            ->where('id', $userId)
            ->first();

        if (! $user) {
            return null;
        }

        return [
            'name' => $user->name,
            'mail' => $user->email,
            'role' => $user->role,
        ];
    }

    public function getUserAction(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser === null) {
            return [
                'status' => 401,
                'data' => [
                    'message' => 'Unauthorized',
                ],
            ];
        }

        $userData = $this->getUser($authUser->id);

        if ($userData === null) {
            return [
                'status' => 404,
                'data' => [
                    'message' => 'User not found',
                ],
            ];
        }

        return [
            'status' => 200,
            'data' => $userData,
        ];
    }

    public function updateUser(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return $user->fresh();
    }

    public function updateProfileAction(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser === null) {
            return [
                'status' => 401,
                'data' => [
                    'message' => 'Unauthorized',
                ],
            ];
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$authUser->id],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $updatedUser = $this->updateUser($authUser, $validated);

        return [
            'status' => 200,
            'data' => [
                'message' => 'Профиль обновлен',
                'user' => [
                    'name' => $updatedUser->name,
                    'mail' => $updatedUser->email,
                    'role' => $updatedUser->role,
                ],
            ],
        ];
    }

    public function getAdminsAction(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser === null) {
            return [
                'status' => 401,
                'data' => ['message' => 'Unauthorized'],
            ];
        }

        if (! in_array($authUser->role, ['admin', 'super_admin'], true)) {
            return [
                'status' => 403,
                'data' => ['message' => 'Forbidden'],
            ];
        }

        $admins = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->latest()
            ->get();

        return [
            'status' => 200,
            'data' => ['admins' => $admins],
        ];
    }

    public function createAdminAction(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser === null) {
            return [
                'status' => 401,
                'data' => ['message' => 'Unauthorized'],
            ];
        }

        if ($authUser->role !== 'super_admin') {
            return [
                'status' => 403,
                'data' => ['message' => 'Only super admin can create admins'],
            ];
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'in:admin,super_admin'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'admin',
        ]);

        return [
            'status' => 201,
            'data' => [
                'message' => 'Админ создан',
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
            ],
        ];
    }
}
