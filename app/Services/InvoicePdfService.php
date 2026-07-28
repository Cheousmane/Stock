<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Support\Money;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items']);

        $company = TenantContext::get();

        $data = [
            'company' => $company,
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'money' => Money::class,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);

        return $pdf->output();
    }
}
