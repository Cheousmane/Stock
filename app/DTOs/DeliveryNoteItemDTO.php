<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DeliveryNoteItemDTO
{
    public function __construct(
        public string $description,
        public int $quantity,
        public ?int $product_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            quantity: (int) $data['quantity'],
            product_id: isset($data['product_id']) ? (int) $data['product_id'] : null,
        );
    }
}
