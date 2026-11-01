<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consultation en lecture seule de l'abonnement côté entreprise.
 * Toute modification est réservée au panneau Super Admin.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly QuotaService $quotas,
    ) {
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('sort')->get();

        return PlanResource::collection($plans)->response();
    }

    public function current(): JsonResponse
    {
        $company = TenantContext::get();

        if ($company === null) {
            return response()->json(['data' => null], Response::HTTP_OK);
        }

        $company = $this->subscriptions->ensureCompanyIsUpToDate($company);
        $subscription = $this->subscriptions->currentSubscription($company);
        $snapshot = $this->quotas->snapshot($company);
        $trialActive = $company->hasActiveTrial();

        return response()->json([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'status' => $company->status,
                ],
                'plan' => $company->plan ? new PlanResource($company->plan) : null,
                'subscription' => $subscription ? new SubscriptionResource($subscription->load('plan')) : null,
                'trial' => [
                    'started_at' => $company->trial_started_at?->toIso8601String(),
                    'ends_at' => $company->trial_ends_at?->toIso8601String(),
                    'active' => $trialActive,
                    'days_remaining' => $trialActive
                        ? max(0, (int) ceil(now()->diffInDays($company->trial_ends_at, false)))
                        : 0,
                ],
                'quotas' => $snapshot,
                'total_frozen' => $snapshot['frozen_total'],
                'can_manage' => false,
            ],
        ], Response::HTTP_OK);
    }
}
