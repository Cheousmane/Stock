<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Services\ProfitService;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateInvoiceAction
{
    public function execute(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            $invoice->update($data);

            $itemsChanged = isset($data['items']);
            if ($itemsChanged) {
                $invoice->items()->delete();

                $companyId = TenantContext::getCompanyId();
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

                    $itemRow = $invoice->items()->create([
                        'company_id' => $companyId,
                        'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                        'description' => $item['description'] ?? '',
                        'quantity' => $quantity,
                        'unit_price_xof' => $unitPrice,
                        'subtotal_xof' => $itemSubtotal,
                        'tax_rate' => $taxRate,
                        'tax_xof' => $itemTax,
                        'total_xof' => $itemTotal,
                    ]);

                    app(ProfitService::class)->calculateItemProfit($itemRow);
                }
            } else {
                $subtotal = (int) $invoice->subtotal_xof;
                $totalTax = (int) $invoice->tax_xof;
            }

            $discount = (int) ($data['discount_xof'] ?? $invoice->discount_xof ?? 0);
            $total = Money::subtract(Money::add($subtotal, $totalTax), $discount);

            if ($total < (int) $invoice->paid_xof) {
                throw ValidationException::withMessages([
                    'discount_xof' => ['Le total après remise ne peut pas être inférieur aux paiements déjà enregistrés.'],
                ]);
            }

            $invoice->update([
                'subtotal_xof' => $subtotal,
                'tax_xof' => $totalTax,
                'discount_xof' => $discount,
                'total_xof' => $total,
                'balance_due_xof' => Money::subtract($total, $invoice->paid_xof ?? 0),
            ]);

            return $invoice->fresh();
        });
    }
}
