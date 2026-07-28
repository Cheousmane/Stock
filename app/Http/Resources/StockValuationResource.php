<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockValuationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'product_id' => $this->product_id,
            'variant_id' => $this->product_variant_id,
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, fn () => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'attributes' => $this->variant->attributes,
            ]),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'batch_reference' => $this->batch_reference,
            'received_at' => $this->received_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
