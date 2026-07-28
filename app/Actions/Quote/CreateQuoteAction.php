<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\DTOs\QuoteDTO;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateQuoteAction
{
    public function execute(QuoteDTO $dto): Quote
    {
        $companyId = TenantContext::getCompanyId();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return $this->tryCreate($dto, $companyId);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) throw $e;
            }
        }

        throw new \RuntimeException('Impossible de créer le devis après 5 tentatives.');
    }

    private function tryCreate(QuoteDTO $dto, int $companyId): Quote
    {
        return DB::transaction(function () use ($dto, $companyId) {
            $number = $this->generateQuoteNumber($companyId);

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

            $quote = Quote::create([
                'company_id' => $companyId,
                'number' => $number,
                'customer_id' => $dto->customer_id,
                'status' => QuoteStatus::Draft,
                'issue_date' => $dto->issue_date ?? now()->toDateString(),
                'expiration_date' => $dto->expiration_date,
                'subtotal_xof' => $subtotal,
                'tax_xof' => $totalTax,
                'discount_xof' => $discount,
                'discount_type' => $dto->discount_type,
                'total_xof' => $total,
                'notes' => $dto->notes,
                'terms' => $dto->terms,
                'metadata' => $dto->metadata,
            ]);

            foreach ($itemData as $data) {
                $quote->items()->create($data);
            }

            return $quote->fresh();
        });
    }

    private function generateQuoteNumber(int $companyId): string
    {
        $prefix = 'DEV-' . now()->format('Y-m') . '-';

        $lastSequence = DB::table('quotes')
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max(DB::raw('CAST(SUBSTRING_INDEX(number, \'-\', -1) AS UNSIGNED)'));

        $next = ($lastSequence ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
