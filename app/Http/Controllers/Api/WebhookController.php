<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\Plan;
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

            $this->syncCompany($stripeId);
        }

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $stripeId = $payload['data']['object']['id'] ?? null;

        if ($stripeId) {
            Subscription::where('stripe_id', $stripeId)
                ->update(['stripe_status' => 'canceled']);

            $this->syncCompany($stripeId);
        }

        return $this->successMethod();
    }

    /**
     * Aligne le statut de l'entreprise sur l'état réel de ses abonnements :
     * abonnement valide => active, sinon retombée sur l'offre gratuite.
     */
    protected function syncCompany(string $stripeId): void
    {
        $subscription = Subscription::where('stripe_id', $stripeId)->first();
        if (!$subscription) {
            return;
        }

        $company = Company::find($subscription->company_id);
        if (!$company) {
            return;
        }

        $covered = Subscription::where('company_id', $company->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->exists();

        if ($covered) {
            $company->forceFill([
                'status' => 'active',
                'trial_ends_at' => null,
                'suspended_at' => null,
            ])->save();
            return;
        }

        $freePlan = Plan::where('slug', 'free')->first();
        $company->forceFill([
            'plan_id' => $freePlan?->id ?? $company->plan_id,
            'status' => 'active',
            'trial_ends_at' => null,
        ])->save();
    }
}
