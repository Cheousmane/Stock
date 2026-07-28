<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryNoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'number' => $this->number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'invoice_id' => $this->invoice_id,
            'invoice' => $this->whenLoaded('invoice', fn() => new InvoiceResource($this->invoice)),
            'status' => $this->status,
            'issue_date' => $this->issue_date?->toIso8601String(),
            'delivery_date' => $this->delivery_date?->toIso8601String(),
            'items' => DeliveryNoteItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->notes,
            'signature' => $this->signature,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
