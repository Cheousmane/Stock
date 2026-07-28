<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Support\Money;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final class QuotePdfService
{
    public function generate(Quote $quote): string
    {
        $quote->loadMissing(['customer', 'items']);

        $company = TenantContext::get();

        $data = [
            'company' => $company,
            'quote' => $quote,
            'customer' => $quote->customer,
            'items' => $quote->items,
            'money' => Money::class,
        ];

        $pdf = Pdf::loadView('pdf.quote', $data);

        return $pdf->output();
    }
}
