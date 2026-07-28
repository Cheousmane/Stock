<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\TenantContext;

class CreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('credit_note');
        $cnId = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;
        $isPatch = $this->isMethod('patch');

        return [
            'number' => [
                $isPatch ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('credit_notes', 'number')
                    ->where('company_id', TenantContext::getCompanyId())
                    ->ignore($cnId)
            ],
            'customer_id' => $isPatch ? ['sometimes', 'exists:customers,id'] : ['required', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'validated', 'refunded'])],
            'issue_date' => $isPatch ? ['sometimes', 'date'] : ['required', 'date'],
            
            'discount_xof' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],

            'items' => $isPatch ? ['sometimes', 'array', 'min:1'] : ['required', 'array', 'min:1'],
            'items.*.product_id' => $isPatch ? ['sometimes', 'exists:products,id'] : ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => $isPatch ? ['sometimes', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => $isPatch ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'items.*.tax_id' => ['nullable', 'exists:taxes,id'],
        ];
    }
}
