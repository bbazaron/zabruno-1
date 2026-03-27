<?php

namespace App\Services;

use App\Models\User;
use App\Services\DTO\LoginDTO;
use App\Services\DTO\SignUpDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function signUp(SignUpDTO $dto): User
    {
        return User::create([
            'name' => $dto->getFirstName().' '.$dto->getLastName(),
            'email' => $dto->getEmail(),
            'password' => $dto->getPassword(),
        ]);
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
}
