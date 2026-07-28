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
        return response()->json(PlanResource::collection($plans), Response::HTTP_OK);
    }

    public function current(): JsonResponse
    {
        $company = TenantContext::get();
        $subscription = Subscription::where('company_id', $company->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['data' => null], Response::HTTP_OK);
        }

        return response()->json(new SubscriptionResource($subscription), Response::HTTP_OK);
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
            'type' => 'default',
            'stripe_id' => $stripeSubscription->stripe_id,
            'stripe_status' => $stripeSubscription->stripe_status,
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => $stripeSubscription->trial_ends_at,
            'ends_at' => $stripeSubscription->ends_at,
        ]);

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
            'stripe_price' => $plan->stripe_price_id,
        ]);

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
