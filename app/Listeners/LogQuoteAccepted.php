<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuoteAccepted;
use App\Services\WebhookDispatcherService;

final class LogQuoteAccepted
{
    public function handle(QuoteAccepted $event): void
    {
        $quote = $event->quote;

        activity()
            ->performedOn($quote)
            ->withProperties([
                'quote_number' => $quote->number,
                'total' => $quote->total_xof,
                'customer_id' => $quote->customer_id,
            ])
            ->event('accepted')
            ->log('Quote accepted');

        app(WebhookDispatcherService::class)->dispatch('quote.accepted', [
            'id' => $quote->id,
            'number' => $quote->number,
            'total_xof' => $quote->total_xof,
            'customer_id' => $quote->customer_id,
        ]);
    }
}
