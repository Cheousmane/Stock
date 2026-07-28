<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_xof' => $this->unit_price_xof,
            'subtotal_xof' => $this->subtotal_xof,
            'tax_rate' => $this->tax_rate,
            'tax_xof' => $this->tax_xof,
            'total_xof' => $this->total_xof,
        ];
    }
}
