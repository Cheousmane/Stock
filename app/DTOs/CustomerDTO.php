<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CustomerDTO
{
    public function __construct(
        public string $name,
        public ?string $code = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $tax_number = null,
        public ?string $registration_number = null,
        public ?int $credit_limit_xof = null,
        public int $balance_xof = 0,
        public ?string $notes = null,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: $data['code'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
            tax_number: $data['tax_number'] ?? null,
            registration_number: $data['registration_number'] ?? null,
            credit_limit_xof: isset($data['credit_limit_xof']) ? (int) $data['credit_limit_xof'] : null,
            balance_xof: (int) ($data['balance_xof'] ?? 0),
            notes: $data['notes'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
