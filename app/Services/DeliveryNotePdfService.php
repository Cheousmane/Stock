<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryNote;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final class DeliveryNotePdfService
{
    public function generate(DeliveryNote $deliveryNote): string
    {
        $deliveryNote->loadMissing(['customer', 'items']);

        $company = TenantContext::get();

        $data = [
            'company' => $company,
            'deliveryNote' => $deliveryNote,
            'customer' => $deliveryNote->customer,
            'items' => $deliveryNote->items,
        ];

        $pdf = Pdf::loadView('pdf.delivery-note', $data);

        return $pdf->output();
    }
}
