<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\NumberToWords;
use App\Support\TenantContext;

final class QuotePdfService
{
    public function __construct(private readonly DocumentPdfService $documents) {}

    public function generate(Quote $quote): string
    {
        $quote->loadMissing(['customer', 'items']);

        $company = TenantContext::get();
        $meta = $company->metadata ?? [];
        $currency = $meta['currency'] ?? 'XOF';

        return $this->documents->render('pdf.quote', [
            'company' => $company,
            'quote' => $quote,
            'customer' => $quote->customer,
            'items' => $quote->items,
            'docTypeKey' => 'quote',
            'docNumber' => $quote->number,
            'statusValue' => $quote->status->value,
            'verificationUrl' => url('/verify/quote/'.$quote->uuid),
            'accent' => '#7c3aed',
            'subtotal' => $quote->subtotal_xof,
            'taxBreakdown' => $this->documents->taxBreakdown($quote->items),
            'taxTotal' => $quote->tax_xof,
            'discount' => $quote->discount_xof,
            'discountType' => $quote->discount_type,
            'total' => $quote->total_xof,
            'paid' => null,
            'balanceDue' => null,
            'amountInWords' => NumberToWords::amountToWords($quote->total_xof, $currency),
            'watermark' => match ($quote->status) {
                QuoteStatus::Draft => 'BROUILLON',
                default => null,
            },
            'watermarkColor' => match ($quote->status) {
                QuoteStatus::Draft => '#64748b',
                default => null,
            },
        ]);
    }
}
