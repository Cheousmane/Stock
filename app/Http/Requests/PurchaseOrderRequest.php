<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\TenantContext;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('purchase_order');
        $poId = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;

        return [
            'number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'number')
                    ->where('company_id', TenantContext::getCompanyId())
                    ->ignore($poId)
            ],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'ordered', 'received', 'cancelled'])],
            'issue_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            
            'discount_xof' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => ['required', 'integer', 'min:0'],
            'items.*.tax_id' => ['nullable', 'exists:taxes,id'],
        ];
    }
}
