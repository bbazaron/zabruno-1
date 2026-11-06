<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmRegistrationRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен',
            'email.email' => 'Некорректный формат email',
            'code.required' => 'Введите код из письма',
            'code.digits' => 'Код должен состоять из 6 цифр',
        ];
    }
}
