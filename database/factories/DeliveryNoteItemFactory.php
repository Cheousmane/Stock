<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeliveryNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteItemFactory extends Factory
{
    protected $model = DeliveryNoteItem::class;

    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }
}
