<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен',
            'email.email' => 'Некорректный формат email',
            'code.required' => 'Введите код из письма',
            'code.digits' => 'Код должен состоять из 6 цифр',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль должен быть минимум 6 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }
}
