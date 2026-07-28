<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Invoice;

final class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        activity()
            ->performedOn($invoice)
            ->withProperties([
                'number' => $invoice->number,
                'total' => $invoice->total_xof,
                'status' => $invoice->status->value,
            ])
            ->event('created')
            ->log('Invoice created');
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('status')) {
            $originalStatus = $invoice->getOriginal('status');

            activity()
                ->performedOn($invoice)
                ->withProperties([
                    'original_status' => $originalStatus?->value,
                    'new_status' => $invoice->status->value,
                    'changed_attributes' => $invoice->getChanges(),
                ])
                ->event('status_changed')
                ->log("Invoice status changed from {$originalStatus?->value} to {$invoice->status->value}");
        }
    }

    public function deleted(Invoice $invoice): void
    {
        activity()
            ->performedOn($invoice)
            ->withProperties([
                'number' => $invoice->number,
                'total' => $invoice->total_xof,
            ])
            ->event('deleted')
            ->log('Invoice deleted');
    }
}
