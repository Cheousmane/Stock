<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Class LoginDTO
 * Immutable DTO for login payload.
 */
final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false
    ) {}

    /**
     * Create DTO from request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            remember: (bool) ($data['remember'] ?? false)
        );
    }
}
