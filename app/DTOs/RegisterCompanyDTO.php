<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Class RegisterCompanyDTO
 * Immutable Data Transfer Object for creating a new company and its owner.
 */
final readonly class RegisterCompanyDTO
{
    public function __construct(
        public string $companyName,
        public string $companySlug,
        public string $userName,
        public string $userEmail,
        public string $userPassword,
        public ?string $companyIndustry = null,
        public ?string $companySize = null
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            companyName: $data['company_name'],
            companySlug: $data['company_slug'],
            userName: $data['name'],
            userEmail: $data['email'],
            userPassword: $data['password'],
            companyIndustry: $data['industry'] ?? null,
            companySize: $data['size'] ?? null
        );
    }
}