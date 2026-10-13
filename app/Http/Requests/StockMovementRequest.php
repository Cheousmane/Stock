<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('company_id', TenantContext::getCompanyId())],
            'type' => ['required', 'string', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('company_id', TenantContext::getCompanyId())],
        ];
    }
}
