<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawEmail = $this->input('parent_email');
        if ($rawEmail === null || (is_string($rawEmail) && trim($rawEmail) === '')) {
            $this->merge(['parent_email' => '']);
        }
    }

    public function rules(): array
    {
        return [
            'child_full_name' => 'required|string|max:255',
            'child_gender' => 'required|in:boy,girl',
            'settlement' => 'required|string|max:255',
            'school' => 'required|string|max:255',
            'class_num' => 'required|string|max:32',
            'class_letter' => 'nullable|string|max:32',
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
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Добавьте хотя бы одну позицию комплекта',
            'items.min' => 'Добавьте хотя бы одну позицию комплекта',
        ];
    }
}
