<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . ' Plan',
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'stripe_price_id' => 'price_' . fake()->unique()->regexify('[a-zA-Z0-9]{14}'),
            'price_xof' => fake()->numberBetween(5000, 100000),
            'currency' => 'xof',
            'trial_days' => 14,
            'is_active' => true,
            'sort' => fake()->numberBetween(1, 10),
        ];
    }
}
