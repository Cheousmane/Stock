<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StockTransferDTO
{
    public function __construct(
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public int $productId,
        public int $quantity,
        public ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fromWarehouseId: (int) $data['from_warehouse_id'],
            toWarehouseId: (int) $data['to_warehouse_id'],
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
            reason: $data['reason'] ?? null,
        );
    }
}
