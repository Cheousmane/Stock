<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->unique()->word() . ' Tax',
            'rate' => fake()->randomElement([5.5, 10, 18, 20]),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'is_composite' => false,
            'is_active' => true,
        ];
    }
}
