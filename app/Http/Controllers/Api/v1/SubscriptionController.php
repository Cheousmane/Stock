<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('sort')->get();
        return PlanResource::collection($plans)->response();
    }

    public function current(): JsonResponse
    {
        $company = TenantContext::get();
        $subscription = Subscription::where('company_id', $company->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->latest()
            ->first();

        if ($subscription) {
            return SubscriptionResource::make($subscription->load('plan'))->response();
        }

        // No Cashier subscription, check if company has a plan assigned
        $company->loadMissing('plan');
        if ($company->plan) {
            return response()->json([
                'data' => [
                    'id' => null,
                    'company_id' => $company->id,
                    'type' => 'default',
                    'stripe_id' => null,
                    'stripe_status' => 'active',
                    'stripe_price' => $company->plan->stripe_price_id,
                    'plan_id' => $company->plan->id,
                    'plan_slug' => $company->plan->slug,
                    'quantity' => 1,
                    'trial_ends_at' => null,
                    'ends_at' => null,
                    'created_at' => $company->created_at?->toIso8601String(),
                    'updated_at' => $company->updated_at?->toIso8601String(),
                    'plan_name' => $company->plan->name,
                ]
            ], Response::HTTP_OK);
        }

        return response()->json(['data' => null], Response::HTTP_OK);
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $user = $request->user();
        $company = TenantContext::get();
        $plan = Plan::findOrFail($request->plan_id);

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($request->payment_method_id);

        $stripeSubscription = $user->newSubscription('default', $plan->stripe_price_id)
            ->create($request->payment_method_id);

        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => $stripeSubscription->stripe_id,
            'stripe_status' => $stripeSubscription->stripe_status,
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => $stripeSubscription->trial_ends_at,
            'ends_at' => $stripeSubscription->ends_at,
        ]);

        // Le paiement (ou l'essai Stripe) lève l'essai / la suspension : compte actif sur ce plan.
        $company->forceFill([
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => null,
            'suspended_at' => null,
        ])->save();

        return response()->json(new SubscriptionResource($subscription), Response::HTTP_CREATED);
    }

    public function cancel(Request $request): JsonResponse
    {
        $company = TenantContext::get();
        $subscription = Subscription::where('company_id', $company->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->latest()
            ->firstOrFail();

        $user = $request->user();
        $stripeSubscription = $user->subscription('default');
        $stripeSubscription->cancel();

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => $stripeSubscription->ends_at(),
        ]);

        return response()->json(new SubscriptionResource($subscription->fresh()), Response::HTTP_OK);
    }

    public function resume(Request $request): JsonResponse
    {
        $company = TenantContext::get();
        $subscription = Subscription::where('company_id', $company->id)
            ->where('stripe_status', 'canceled')
            ->latest()
            ->firstOrFail();

        $user = $request->user();
        $stripeSubscription = $user->subscription('default');
        $stripeSubscription->resume();

        $subscription->update([
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);

        $company->forceFill([
            'status' => 'active',
            'trial_ends_at' => null,
            'suspended_at' => null,
        ])->save();

        return response()->json(new SubscriptionResource($subscription->fresh()), Response::HTTP_OK);
    }

    public function swap(string $planSlug, Request $request): JsonResponse
    {
        $company = TenantContext::get();
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        $subscription = Subscription::where('company_id', $company->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->latest()
            ->firstOrFail();

        $user = $request->user();
        $stripeSubscription = $user->subscription('default');
        $stripeSubscription->swap($plan->stripe_price_id);

        $subscription->update([
            'plan_id' => $plan->id,
            'stripe_price' => $plan->stripe_price_id,
        ]);

        $company->forceFill([
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => null,
            'suspended_at' => null,
        ])->save();

        return response()->json(new SubscriptionResource($subscription->fresh()), Response::HTTP_OK);
    }

    public function invoicePortal(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->createOrGetStripeCustomer();

        return response()->json([
            'url' => $user->billingPortalUrl(route('api.v1.dashboard')),
        ], Response::HTTP_OK);
    }
}
