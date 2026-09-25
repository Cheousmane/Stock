<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data for creating a new company + owner from a verified Google identity.
 * The e-mail is pre-verified by Google: no password, no verification code.
 */
final readonly class GoogleRegisterDTO
{
    public function __construct(
        public string $googleId,
        public string $userEmail,
        public string $userName,
        public string $companyName,
        public string $companySlug,
        public ?string $avatar = null,
        public ?string $companyIndustry = null,
        public ?string $companySize = null,
        public ?int $planId = null,
    ) {}
}
