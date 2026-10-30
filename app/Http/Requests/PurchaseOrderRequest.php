<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'fournisseur',
            'warehouse_id' => 'entrepôt',
            'issue_date' => 'date d\'émission',
            'expected_delivery_date' => 'date de livraison prévue',
            'items' => 'lignes',
            'items.*.product_id' => 'produit',
            'items.*.quantity' => 'quantité',
            'items.*.unit_price_xof' => 'prix unitaire',
        ];
    }

    public function rules(): array
    {
        $routeParam = $this->route('purchase_order');
        $poId = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;
        $companyId = TenantContext::getCompanyId();

        return [
            'number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'number')
                    ->where('company_id', TenantContext::getCompanyId())
                    ->ignore($poId),
            ],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'status' => ['nullable', 'string', Rule::in(['draft', 'ordered', 'received', 'cancelled'])],
            'issue_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:issue_date'],

            'discount_xof' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => ['required', 'integer', 'min:0'],
            'items.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
        ];
    }
}
