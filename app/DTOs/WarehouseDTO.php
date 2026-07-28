<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class WarehouseDTO
{
    public function __construct(
        public string $name,
        public ?string $code = null,
        public ?string $location = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $phone = null,
        public ?string $email = null,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: $data['code'] ?? null,
            location: $data['location'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
