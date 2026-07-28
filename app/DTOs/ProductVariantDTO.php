<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ProductVariantDTO
{
    public function __construct(
        public int $product_id,
        public ?string $sku,
        public ?string $barcode,
        public ?int $price_xof,
        public ?int $purchase_price_xof,
        public ?int $cost_price_xof,
        public ?int $wholesale_price_xof,
        public int $quantity,
        public ?array $attributes,
        public bool $is_active,
        public int $sort_order,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_id: (int) ($data['product_id'] ?? 0),
            sku: $data['sku'] ?? null,
            barcode: $data['barcode'] ?? null,
            price_xof: isset($data['price_xof']) ? (int) $data['price_xof'] : null,
            purchase_price_xof: isset($data['purchase_price_xof']) ? (int) $data['purchase_price_xof'] : null,
            cost_price_xof: isset($data['cost_price_xof']) ? (int) $data['cost_price_xof'] : null,
            wholesale_price_xof: isset($data['wholesale_price_xof']) ? (int) $data['wholesale_price_xof'] : null,
            quantity: (int) ($data['quantity'] ?? 0),
            attributes: $data['attributes'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
            sort_order: (int) ($data['sort_order'] ?? 0),
        );
    }
}
