<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\ProductVariantDTO;
use App\Models\ProductVariant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateProductVariantAction
{
    public function execute(ProductVariantDTO $dto): ProductVariant
    {
        return DB::transaction(function () use ($dto) {
            return ProductVariant::create([
                'company_id' => TenantContext::getCompanyId(),
                'product_id' => $dto->product_id,
                'sku' => $dto->sku,
                'barcode' => $dto->barcode,
                'price_xof' => $dto->price_xof,
                'purchase_price_xof' => $dto->purchase_price_xof,
                'cost_price_xof' => $dto->cost_price_xof,
                'wholesale_price_xof' => $dto->wholesale_price_xof,
                'quantity' => $dto->quantity,
                'attributes' => $dto->attributes,
                'is_active' => $dto->is_active,
                'sort_order' => $dto->sort_order,
            ]);
        });
    }
}
