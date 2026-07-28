<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Notifications\InvoicePaidNotification;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Notification;

final class SendInvoicePaidNotification
{
    public function handle(InvoicePaid $event): void
    {
        $company = $event->invoice->company;

        if ($company === null) {
            return;
        }

        $users = $company->users;

        if ($users->isNotEmpty()) {
            Notification::send($users, new InvoicePaidNotification($event->invoice, $event->payment));
        }

        app(WebhookDispatcherService::class)->dispatch('invoice.paid', [
            'id' => $event->invoice->id,
            'number' => $event->invoice->number,
            'total_xof' => $event->invoice->total_xof,
            'paid_xof' => $event->invoice->paid_xof,
            'payment_id' => $event->payment->id,
            'payment_method' => $event->payment->method,
            'payment_amount' => $event->payment->amount_xof,
        ]);
    }
}
