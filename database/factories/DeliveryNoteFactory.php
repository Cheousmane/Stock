<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeliveryNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'number' => 'BL-' . now()->format('Y-m') . '-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'issue_date' => now()->toDateString(),
        ];
    }
}
