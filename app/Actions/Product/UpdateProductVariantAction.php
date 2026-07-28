<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\DTOs\ProductVariantDTO;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class UpdateProductVariantAction
{
    public function execute(ProductVariant $variant, ProductVariantDTO $dto): ProductVariant
    {
        return DB::transaction(function () use ($variant, $dto) {
            $variant->update([
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

            return $variant->fresh();
        });
    }
}
