<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 500000);
        $tax = (int) round($subtotal * 0.18);
        $total = $subtotal + $tax;

        return [
            'uuid' => (string) Str::uuid(),
            'number' => 'INV-' . now()->format('Y-m') . '-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal_xof' => $subtotal,
            'tax_xof' => $tax,
            'discount_xof' => 0,
            'total_xof' => $total,
            'paid_xof' => 0,
            'balance_due_xof' => $total,
        ];
    }
}
