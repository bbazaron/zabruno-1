<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'registration_token' => 'required|string',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен',
            'email.email' => 'Некорректный формат email',
            'registration_token.required' => 'Сначала подтвердите почту',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль должен быть минимум 6 символов',
        ];
    }
}
