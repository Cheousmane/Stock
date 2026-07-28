<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Services\WebhookDispatcherService;

final class LogInvoiceCreated
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        activity()
            ->performedOn($invoice)
            ->withProperties([
                'invoice_number' => $invoice->number,
                'total' => $invoice->total_xof,
                'status' => $invoice->status->value,
            ])
            ->event('created')
            ->log('Invoice created');

        app(WebhookDispatcherService::class)->dispatch('invoice.created', [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'total_xof' => $invoice->total_xof,
            'status' => $invoice->status->value,
            'customer_id' => $invoice->customer_id,
        ]);
    }
}
