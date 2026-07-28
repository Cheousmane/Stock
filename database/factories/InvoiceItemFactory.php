<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->numberBetween(1000, 50000);
        $subtotal = $quantity * $unitPrice;
        $taxRate = 18;
        $tax = (int) round($subtotal * $taxRate / 100);

        return [
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price_xof' => $unitPrice,
            'subtotal_xof' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_xof' => $tax,
            'total_xof' => $subtotal + $tax,
        ];
    }
}
