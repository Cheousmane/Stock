<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\WebhookDispatcherService;

final class LogPaymentReceived
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        activity()
            ->performedOn($payment)
            ->withProperties([
                'payment_amount' => $payment->amount_xof,
                'payment_method' => $payment->method,
                'payment_reference' => $payment->reference,
                'invoice_id' => $payment->invoice_id,
            ])
            ->event('received')
            ->log('Payment received');

        app(WebhookDispatcherService::class)->dispatch('payment.received', [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'amount_xof' => $payment->amount_xof,
            'method' => $payment->method,
            'reference' => $payment->reference,
        ]);
    }
}
