<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DeliveryNoteDTO
{
    public function __construct(
        public int $customer_id,
        public string $issue_date,
        public array $items,
        public ?int $invoice_id = null,
        public ?string $delivery_date = null,
        public ?string $notes = null,
        public ?string $signature = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            issue_date: $data['issue_date'],
            items: $data['items'] ?? [],
            invoice_id: isset($data['invoice_id']) ? (int) $data['invoice_id'] : null,
            delivery_date: $data['delivery_date'] ?? null,
            notes: $data['notes'] ?? null,
            signature: $data['signature'] ?? null,
        );
    }
}
