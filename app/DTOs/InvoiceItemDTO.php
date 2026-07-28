<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class InvoiceItemDTO
{
    public function __construct(
        public ?int $productId = null,
        public string $description = '',
        public int $quantity = 0,
        public int $unitPriceXof = 0,
        public float $taxRate = 0.0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            description: $data['description'],
            quantity: (int) $data['quantity'],
            unitPriceXof: (int) $data['unit_price_xof'],
            taxRate: (float) ($data['tax_rate'] ?? 0),
        );
    }
}
