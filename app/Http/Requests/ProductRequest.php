<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\TenantContext;

/**
 * Validate product creation / update requests.
 */
class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');
        $routeParam = $this->route('product');
        $productId = $routeParam instanceof Model ? $routeParam->getKey() : $routeParam;

        return [
            'name' => $isPatch ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'sku' => $isPatch
                ? ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', TenantContext::getCompanyId())->ignore($productId)]
                : ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', TenantContext::getCompanyId())->ignore($productId)],
            'price_xof' => ['sometimes', 'integer', 'min:0'],
            'cost_price_xof' => ['nullable', 'integer', 'min:0'],
            'purchase_price_xof' => ['nullable', 'integer', 'min:0'],
            'wholesale_price_xof' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    protected function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        if ($this->isMethod('patch')) return;

        $validator->after(function ($validator) {
            $data = $validator->getData();
            if (!isset($data['price_xof']) || $data['price_xof'] === '' || $data['price_xof'] === null) {
                $validator->errors()->add('price_xof', 'Le prix de vente est requis.');
            }
        });
    }
}
