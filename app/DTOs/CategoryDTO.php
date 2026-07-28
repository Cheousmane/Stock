<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Str;

final readonly class CategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?int $parent_id = null,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? Str::slug($data['name']),
            description: $data['description'] ?? null,
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
