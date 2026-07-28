<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class QuoteDTO
{
    public function __construct(
        public int $customer_id,
        public string $expiration_date,
        public array $items,
        public ?string $issue_date = null,
        public ?int $discount_xof = null,
        public ?string $discount_type = null,
        public ?string $notes = null,
        public ?string $terms = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            expiration_date: $data['expiration_date'],
            items: $data['items'] ?? [],
            issue_date: $data['issue_date'] ?? null,
            discount_xof: isset($data['discount_xof']) ? (int) $data['discount_xof'] : null,
            discount_type: $data['discount_type'] ?? null,
            notes: $data['notes'] ?? null,
            terms: $data['terms'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
