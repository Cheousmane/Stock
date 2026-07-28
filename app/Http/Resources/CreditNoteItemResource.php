<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price_xof' => $this->unit_price_xof,
            'subtotal_xof' => $this->subtotal_xof,
            'tax_amount_xof' => $this->tax_amount_xof,
            'discount_amount_xof' => $this->discount_amount_xof,
            'total_xof' => $this->total_xof,
        ];
    }
}
