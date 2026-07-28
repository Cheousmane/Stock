<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price_xof' => $this->price_xof,
            'purchase_price_xof' => $this->purchase_price_xof,
            'cost_price_xof' => $this->cost_price_xof,
            'wholesale_price_xof' => $this->wholesale_price_xof,
            'quantity' => $this->quantity,
            'attributes' => $this->attributes,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
