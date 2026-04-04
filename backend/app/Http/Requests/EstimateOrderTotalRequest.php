<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstimateOrderTotalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('kitLines') && ! $this->has('items')) {
            $merge['items'] = $this->input('kitLines');
        }

        if ($this->has('childGender') && ! $this->has('child_gender')) {
            $merge['child_gender'] = $this->input('childGender');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        $lines = $this->input('items', []);
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
    }

    public function rules(): array
    {
        return [
            'child_gender' => 'nullable|in:boy,girl',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size_override' => 'nullable|string|max:255',
            'items.*.line_comment' => 'nullable|string|max:255',
        ];
    }
}
