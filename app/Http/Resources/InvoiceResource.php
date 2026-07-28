<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'number' => $this->number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'status' => $this->status,
            'issue_date' => $this->issue_date?->toIso8601String(),
            'due_date' => $this->due_date?->toIso8601String(),
            'subtotal_xof' => $this->subtotal_xof,
            'tax_xof' => $this->tax_xof,
            'discount_xof' => $this->discount_xof,
            'discount_type' => $this->discount_type,
            'total_xof' => $this->total_xof,
            'paid_xof' => $this->paid_xof,
            'balance_due_xof' => $this->balance_due_xof,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
