<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Resource representation for a Product.
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'sku' => $this->sku,
            'price_xof' => $this->price_xof,
            'cost_price_xof' => $this->cost_price_xof,
            'description' => $this->description,
            'quantity' => $this->quantity ?? 0,
            'min_stock' => $this->min_stock ?? 0,
            'is_active' => $this->is_active ?? true,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'unit_id' => $this->unit_id,
            'barcode' => $this->barcode,
            'purchase_price_xof' => $this->purchase_price_xof,
            'wholesale_price_xof' => $this->wholesale_price_xof,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
