<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryNote;
use App\Support\TenantContext;

final class DeliveryNotePdfService
{
    public function __construct(private readonly DocumentPdfService $documents) {}

    public function generate(DeliveryNote $deliveryNote): string
    {
        $deliveryNote->loadMissing(['customer', 'items', 'invoice']);
        $deliveryNote->loadMissing('items.product.unit');

        $company = TenantContext::get();

        return $this->documents->render('pdf.delivery-note', [
            'company' => $company,
            'deliveryNote' => $deliveryNote,
            'customer' => $deliveryNote->customer,
            'items' => $deliveryNote->items,
            'docTypeKey' => 'delivery_note',
            'docNumber' => $deliveryNote->number,
            'statusValue' => $deliveryNote->status->value,
            'verificationUrl' => url('/verify/delivery-note/'.$deliveryNote->uuid),
            'accent' => '#059669',
            'signatureImage' => $this->signatureSource($deliveryNote),
            'signatureDate' => $deliveryNote->delivery_date?->format('d/m/Y'),
            'invoiceNumber' => $deliveryNote->invoice?->number,
        ]);
    }

    private function signatureSource(DeliveryNote $deliveryNote): ?string
    {
        $signature = $deliveryNote->signature;

        if (! $signature) {
            return null;
        }

        return str_starts_with($signature, 'data:image') ? $signature : null;
    }
}
