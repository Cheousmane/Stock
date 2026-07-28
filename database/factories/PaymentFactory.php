<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'method' => fake()->randomElement(['cash', 'bank', 'mobile_money']),
            'amount_xof' => fake()->numberBetween(1000, 100000),
            'status' => 'completed',
            'payment_date' => now()->toDateString(),
        ];
    }
}
