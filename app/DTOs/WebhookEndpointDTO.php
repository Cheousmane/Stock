<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class WebhookEndpointDTO
{
    public function __construct(
        public string $name,
        public string $url,
        public ?string $secret,
        public array $events,
        public bool $is_active,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            url: $data['url'],
            secret: $data['secret'] ?? null,
            events: $data['events'],
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
