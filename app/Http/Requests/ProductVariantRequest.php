<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('product_variant');

        return [
            'product_id' => 'nullable|exists:products,id',
            'sku' => 'nullable|string|max:100|unique:product_variants,sku,' . $variantId . ',id',
            'barcode' => 'nullable|string|max:100',
            'price_xof' => 'nullable|integer|min:0',
            'purchase_price_xof' => 'nullable|integer|min:0',
            'cost_price_xof' => 'nullable|integer|min:0',
            'wholesale_price_xof' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'attributes.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}
