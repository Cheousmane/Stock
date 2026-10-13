<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('company_id', TenantContext::getCompanyId())],
            'to_warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('company_id', TenantContext::getCompanyId()), 'different:from_warehouse_id'],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('company_id', TenantContext::getCompanyId())],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
