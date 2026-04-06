<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\Sanctum\PersonalAccessToken;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        $camelToSnake = [
            'childFullName' => 'child_full_name',
            'childGender' => 'child_gender',
            'classNum' => 'class_num',
            'classLetter' => 'class_letter',
            'schoolYear' => 'school_year',
            'sizeFromTable' => 'size_from_table',
            'heightCm' => 'height_cm',
            'chestCm' => 'chest_cm',
            'waistCm' => 'waist_cm',
            'hipsCm' => 'hips_cm',
            'figureComment' => 'figure_comment',
            'kitComment' => 'kit_comment',
            'parentFullName' => 'parent_full_name',
            'parentPhone' => 'parent_phone',
            'parentEmail' => 'parent_email',
            'messengerMax' => 'messenger_max',
            'messengerTelegram' => 'messenger_telegram',
            'recipientIsCustomer' => 'recipient_is_customer',
            'recipientName' => 'recipient_name',
            'recipientPhone' => 'recipient_phone',
            'termsAccepted' => 'terms_accepted',
            'kitLines' => 'items',
        ];

        foreach ($camelToSnake as $camel => $snake) {
            if ($this->has($camel) && ! $this->has($snake)) {
                $merge[$snake] = $this->input($camel);
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        // Позиции комплекта: kitLines / items → items с полями в snake_case
        $lines = $this->input('items', $this->input('kitLines', []));
        if (is_array($lines) && $lines !== []) {
            $normalized = [];
            foreach ($lines as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $normalized[] = [
                    'product_name' => $row['product_name'] ?? $row['productName'] ?? '',
                    'quantity' => $row['quantity'] ?? 1,
                    'size_override' => $row['size_override'] ?? $row['sizeOverride'] ?? null,
                    'line_comment' => $row['line_comment'] ?? $row['lineComment'] ?? null,
                ];
            }
            $this->merge(['items' => $normalized]);
        }

        $rawEmail = $this->input('parent_email');
        $emailTrim = is_string($rawEmail) ? trim($rawEmail) : '';
        if ($emailTrim === '') {
            $user = $this->userFromBearerToken();
            $fallback = '';
            if ($user !== null) {
                $accountEmail = trim((string) $user->email);
                if ($accountEmail !== '' && filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
                    $fallback = $accountEmail;
                }
            }
            $this->merge(['parent_email' => $fallback]);
        }
    }

    private function userFromBearerToken(): ?User
    {
        $token = $this->bearerToken();
        if ($token === null || $token === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken === null) {
            return null;
        }

        $user = $accessToken->tokenable;

        return $user instanceof User ? $user : null;
    }

    public function rules(): array
    {
        return [
            'child_full_name' => 'required|string|max:255',
            'child_gender' => 'required|in:boy,girl',
            'settlement' => 'required|string|max:255',
            'school' => 'required|string|max:255',
            'class_num' => 'required|string|max:32',
            'class_letter' => 'required|string|max:32',
            'school_year' => 'required|string|max:32',

            'size_from_table' => 'required|string|max:255',
            'height_cm' => 'nullable|string|max:32',
            'chest_cm' => 'nullable|string|max:32',
            'waist_cm' => 'nullable|string|max:32',
            'hips_cm' => 'nullable|string|max:32',
            'figure_comment' => 'nullable|string|max:5000',

            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size_override' => 'nullable|string|max:255',
            'items.*.line_comment' => 'nullable|string|max:255',

            'kit_comment' => 'nullable|string|max:5000',

            'parent_full_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:32',
            'parent_email' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }
                $t = trim($value);
                if ($t === '') {
                    return;
                }
                if (! filter_var($t, FILTER_VALIDATE_EMAIL)) {
                    $fail('Укажите корректный email или оставьте поле пустым.');
                }
            }],
            'messenger_max' => 'nullable|string|max:255',
            'messenger_telegram' => 'nullable|string|max:255',

            'recipient_is_customer' => 'required|boolean',
            'recipient_name' => 'nullable|required_if:recipient_is_customer,false|string|max:255',
            'recipient_phone' => 'required|string|max:32',

            'terms_accepted' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'terms_accepted.accepted' => 'Нужно согласие со сроками изготовления и условиями предзаказа',
            'items.required' => 'Добавьте хотя бы одну позицию комплекта',
            'items.min' => 'Добавьте хотя бы одну позицию комплекта',
        ];
    }
}
