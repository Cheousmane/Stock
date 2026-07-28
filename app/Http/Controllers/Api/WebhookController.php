<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierWebhookController
{
    protected function handleInvoicePaid(array $payload): Response
    {
        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $stripeId = $payload['data']['object']['id'] ?? null;
        $status = $payload['data']['object']['status'] ?? null;

        if ($stripeId && $status) {
            Subscription::where('stripe_id', $stripeId)
                ->update(['stripe_status' => $status]);
        }

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $stripeId = $payload['data']['object']['id'] ?? null;

        if ($stripeId) {
            Subscription::where('stripe_id', $stripeId)
                ->update(['stripe_status' => 'canceled']);
        }

        return $this->successMethod();
    }
}
