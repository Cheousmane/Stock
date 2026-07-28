<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'from_warehouse' => $this->whenLoaded('fromWarehouse', fn () => [
                'id' => $this->fromWarehouse->id,
                'name' => $this->fromWarehouse->name,
            ]),
            'to_warehouse' => $this->whenLoaded('toWarehouse', fn () => [
                'id' => $this->toWarehouse->id,
                'name' => $this->toWarehouse->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'reason' => $this->reason,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
