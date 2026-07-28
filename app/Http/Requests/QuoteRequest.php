<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');

        $rules = [
            'customer_id' => $isPatch ? ['sometimes', 'integer', 'exists:customers,id'] : ['required', 'integer', 'exists:customers,id'],
            'issue_date' => $isPatch ? ['sometimes', 'date'] : ['required', 'date'],
            'expiration_date' => $isPatch ? ['sometimes', 'date', 'after_or_equal:issue_date'] : ['required', 'date', 'after_or_equal:issue_date'],
            'items' => $isPatch ? ['sometimes', 'array', 'min:1'] : ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => $isPatch ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'items.*.quantity' => $isPatch ? ['sometimes', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => $isPatch ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['numeric', 'min:0', 'max:100'],
            'discount_xof' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ];

        return $rules;
    }
}
