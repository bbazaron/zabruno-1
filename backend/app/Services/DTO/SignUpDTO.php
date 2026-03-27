<?php

namespace App\Services\DTO;


use App\Http\Requests\SignUpRequest;

class SignUpDTO
{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private string $email,
        private string $password,
    ){}

    public static function fromRequest(SignUpRequest $request): self
    {
        return new self(
            $request->get('firstName'),
            $request->get('lastName'),
            $request->get('email'),
            $request->get('password'),
        );
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

}
