<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class QuoteItemDTO
{
    public function __construct(
        public string $description,
        public int $quantity,
        public int $unit_price_xof,
        public ?int $product_id = null,
        public float $tax_rate = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            quantity: (int) $data['quantity'],
            unit_price_xof: (int) $data['unit_price_xof'],
            product_id: isset($data['product_id']) ? (int) $data['product_id'] : null,
            tax_rate: (float) ($data['tax_rate'] ?? 0),
        );
    }
}
