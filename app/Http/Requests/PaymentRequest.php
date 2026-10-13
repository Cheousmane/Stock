<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('company_id', TenantContext::getCompanyId())],
            'method' => ['required', 'string', 'in:cash,bank,stripe,mobile_money'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount_xof' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
