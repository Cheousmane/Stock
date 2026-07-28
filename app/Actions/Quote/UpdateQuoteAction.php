<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class UpdateQuoteAction
{
    public function execute(Quote $quote, array $data): Quote
    {
        $quote->update($data);

        if (isset($data['items'])) {
            $quote->items()->delete();
            $subtotal = 0;
            $totalTax = 0;

            foreach ($data['items'] as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = (int) ($item['unit_price_xof'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? 0);
                $itemSubtotal = Money::multiply($unitPrice, $quantity);
                $itemTax = Money::percent($itemSubtotal, $taxRate);
                $itemTotal = Money::add($itemSubtotal, $itemTax);
                $subtotal = Money::add($subtotal, $itemSubtotal);
                $totalTax = Money::add($totalTax, $itemTax);

                $quote->items()->create([
                    'company_id' => $quote->company_id,
                    'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price_xof' => $unitPrice,
                    'subtotal_xof' => $itemSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_xof' => $itemTax,
                    'total_xof' => $itemTotal,
                ]);
            }

            $discount = $data['discount_xof'] ?? $quote->discount_xof ?? 0;
            $total = Money::subtract(Money::add($subtotal, $totalTax), $discount);

            $quote->update([
                'subtotal_xof' => $subtotal,
                'tax_xof' => $totalTax,
                'total_xof' => $total,
            ]);
        }

        return $quote->fresh();
    }
}
