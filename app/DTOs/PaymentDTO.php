<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class PaymentDTO
{
    public function __construct(
        public int $invoice_id,
        public int $amount_xof,
        public string $payment_date,
        public ?string $method = null,
        public ?string $reference = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $created_by = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoice_id: (int) $data['invoice_id'],
            amount_xof: (int) $data['amount_xof'],
            payment_date: $data['payment_date'],
            method: $data['method'] ?? null,
            reference: $data['reference'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
            created_by: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }
}
