<?php

namespace App\Services\DTO;

use App\Http\Requests\LoginRequest;

class LoginDTO
{
    public function __construct(
        private string $email,
        private string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            $validated['email'],
            $validated['password'],
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
