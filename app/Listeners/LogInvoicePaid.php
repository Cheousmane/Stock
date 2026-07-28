<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoicePaid;

final class LogInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $payment = $event->payment;

        activity()
            ->performedOn($invoice)
            ->withProperties([
                'invoice_number' => $invoice->number,
                'payment_amount' => $payment->amount_xof,
                'payment_method' => $payment->method,
                'payment_reference' => $payment->reference,
            ])
            ->event('paid')
            ->log('Invoice paid');
    }
}
