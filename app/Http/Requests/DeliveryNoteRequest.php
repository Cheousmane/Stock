<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryNoteRequest extends FormRequest
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
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'issue_date' => $isPatch ? ['sometimes', 'date'] : ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'items' => $isPatch ? ['sometimes', 'array', 'min:1'] : ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => $isPatch ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'items.*.quantity' => $isPatch ? ['sometimes', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ];

        return $rules;
    }
}
