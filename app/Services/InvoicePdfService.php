<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\NumberToWords;
use App\Support\TenantContext;

final class InvoicePdfService
{
    public function __construct(private readonly DocumentPdfService $documents) {}

    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items']);

        $company = TenantContext::get();
        $meta = $company->metadata ?? [];
        $currency = $meta['currency'] ?? 'XOF';

        $invoice->load(['payments' => fn ($q) => $q->where('status', 'completed')->orderBy('payment_date')]);

        return $this->documents->render('pdf.invoice', [
            'company' => $company,
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'docTypeKey' => 'invoice',
            'docNumber' => $invoice->number,
            'statusValue' => $invoice->status->value,
            'verificationUrl' => url('/verify/invoice/'.$invoice->uuid),
            'accent' => '#2563eb',
            'subtotal' => $invoice->subtotal_xof,
            'taxBreakdown' => $this->documents->taxBreakdown($invoice->items),
            'taxTotal' => $invoice->tax_xof,
            'discount' => $invoice->discount_xof,
            'discountType' => $invoice->discount_type,
            'total' => $invoice->total_xof,
            'paid' => $invoice->paid_xof,
            'balanceDue' => $invoice->balance_due_xof,
            'amountInWords' => NumberToWords::amountToWords($invoice->total_xof, $currency),
            'payments' => $invoice->payments,
            'bankName' => $meta['bank_name'] ?? null,
            'bankAccount' => $meta['bank_account'] ?? null,
            'bankSwift' => $meta['bank_swift'] ?? null,
            'watermark' => match ($invoice->status) {
                InvoiceStatus::Draft => 'BROUILLON',
                InvoiceStatus::Overdue => 'EN RETARD',
                InvoiceStatus::Cancelled => 'ANNULÉ',
                default => null,
            },
            'watermarkColor' => match ($invoice->status) {
                InvoiceStatus::Draft => '#64748b',
                InvoiceStatus::Overdue => '#d97706',
                InvoiceStatus::Cancelled => '#dc2626',
                default => null,
            },
        ]);
    }
}
