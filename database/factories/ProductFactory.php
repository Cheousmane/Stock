<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->word(),
            'sku' => strtoupper(fake()->bothify('SKU-####-???')),
            'price' => fake()->numberBetween(1000, 100000),
            'description' => fake()->sentence(),
            'purchase_price_xof' => fake()->numberBetween(500, 50000),
            'min_stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
