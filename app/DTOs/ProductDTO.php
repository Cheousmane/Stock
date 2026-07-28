<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ProductDTO
{
    public function __construct(
        public string $name,
        public string $sku,
        public int $priceXof,
        public ?string $description = null,
        public ?int $costPriceXof = null,
        public ?int $wholesalePriceXof = null,
        public ?int $quantity = null,
        public ?int $minStock = null,
        public ?bool $isActive = null,
        public ?string $barcode = null,
        public ?int $categoryId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $price = (int) ($data['price_xof'] ?? $data['price'] ?? 0);
        $costPrice = isset($data['cost_price_xof']) ? (int) $data['cost_price_xof'] : (isset($data['purchase_price_xof']) ? (int) $data['purchase_price_xof'] : null);

        return new self(
            name: $data['name'],
            sku: $data['sku'],
            priceXof: $price,
            description: $data['description'] ?? null,
            costPriceXof: $costPrice,
            wholesalePriceXof: isset($data['wholesale_price_xof']) ? (int) $data['wholesale_price_xof'] : null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
            minStock: isset($data['min_stock']) ? (int) $data['min_stock'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            barcode: $data['barcode'] ?? null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
        );
    }
}
