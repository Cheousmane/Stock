<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\DTOs\InvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceCreated;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ProfitService;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function execute(InvoiceDTO $dto): Invoice
    {
        $companyId = TenantContext::getCompanyId();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return $this->tryCreate($dto, $companyId);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) throw $e;
            }
        }

        throw new \RuntimeException('Impossible de créer la facture après 5 tentatives.');
    }

    private function tryCreate(InvoiceDTO $dto, int $companyId): Invoice
    {
        return DB::transaction(function () use ($dto, $companyId) {
            $number = $this->generateInvoiceNumber($companyId);

            $subtotal = 0;
            $totalTax = 0;
            $itemData = [];

            foreach ($dto->items as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = (int) ($item['unit_price_xof'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                $itemSubtotal = Money::multiply($unitPrice, $quantity);
                $itemTax = Money::percent($itemSubtotal, $taxRate);
                $itemTotal = Money::add($itemSubtotal, $itemTax);

                $subtotal = Money::add($subtotal, $itemSubtotal);
                $totalTax = Money::add($totalTax, $itemTax);

                $itemData[] = [
                    'company_id' => $companyId,
                    'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price_xof' => $unitPrice,
                    'subtotal_xof' => $itemSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_xof' => $itemTax,
                    'total_xof' => $itemTotal,
                ];
            }

            $discount = $dto->discount_xof ?? 0;
            $total = Money::subtract(Money::add($subtotal, $totalTax), $discount);

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'number' => $number,
                'customer_id' => $dto->customer_id,
                'status' => InvoiceStatus::Draft,
                'issue_date' => $dto->issue_date ?? now()->toDateString(),
                'due_date' => $dto->due_date,
                'subtotal_xof' => $subtotal,
                'tax_xof' => $totalTax,
                'discount_xof' => $discount,
                'discount_type' => $dto->discount_type,
                'total_xof' => $total,
                'paid_xof' => 0,
                'balance_due_xof' => $total,
                'notes' => $dto->notes,
                'terms' => $dto->terms,
                'metadata' => $dto->metadata,
            ]);

            foreach ($itemData as $data) {
                $item = $invoice->items()->create($data);
                app(ProfitService::class)->calculateItemProfit($item);
            }

            $invoice->refresh();

            event(new InvoiceCreated($invoice));

            return $invoice->fresh();
        });
    }

    private function generateInvoiceNumber(int $companyId): string
    {
        $prefix = 'INV-' . now()->format('Y-m') . '-';

        $lastSequence = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max(DB::raw('CAST(SUBSTRING_INDEX(number, \'-\', -1) AS UNSIGNED)'));

        $next = ($lastSequence ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
