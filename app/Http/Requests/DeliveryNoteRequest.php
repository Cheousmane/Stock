<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');
        $companyId = TenantContext::getCompanyId();

        $rules = [
            'customer_id' => $isPatch
                ? ['sometimes', 'integer', Rule::exists('customers', 'id')->where('company_id', $companyId)]
                : ['required', 'integer', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('invoices', 'id')
                    ->where('company_id', $companyId)
                    ->when($this->input('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId)),
            ],
            'issue_date' => $isPatch ? ['sometimes', 'date'] : ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'items' => $isPatch ? ['sometimes', 'array', 'min:1'] : ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.description' => $isPatch ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'items.*.quantity' => $isPatch ? ['sometimes', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ];

        return $rules;
    }
}
