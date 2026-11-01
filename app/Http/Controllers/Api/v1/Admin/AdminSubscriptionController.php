<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Plan;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestion manuelle et centralisée des abonnements depuis le panneau Super Admin.
 */
class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly QuotaService $quotas,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $sortable = ['name', 'status', 'plan_id', 'trial_ends_at', 'created_at', 'id'];
        $sort = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        $companies = Company::with(['plan', 'activeSubscription.plan'])
            ->withCount(['users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('plan_id'), fn ($query, $planId) => $query->where('plan_id', $planId))
            ->when($request->input('subscription_status') === 'trial', function ($query) {
                $query->where('status', 'trial')->where('trial_ends_at', '>', now());
            })
            ->when($request->input('subscription_status') === 'expiring', function ($query) {
                $query->whereHas('activeSubscription', function ($q) {
                    $q->whereNotNull('ends_at')->where('ends_at', '<=', now()->addDays(7));
                });
            })
            ->when($request->input('subscription_status') === 'frozen', function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('products', fn ($p) => $p->withoutGlobalScopes()->whereNotNull('frozen_at'))
                        ->orWhereHas('invoices', fn ($p) => $p->withoutGlobalScopes()->whereNotNull('frozen_at'))
                        ->orWhereHas('users', fn ($p) => $p->whereNotNull('frozen_at'));
                });
            })
            ->orderBy($sort, $order)
            ->paginate(min($request->integer('per_page', 20), 100));

        $companies->getCollection()->transform(fn (Company $company) => $this->summary($company));

        return response()->json($companies, Response::HTTP_OK);
    }

    public function plans(): JsonResponse
    {
        $counts = Company::selectRaw('plan_id, COUNT(*) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        $plans = Plan::orderBy('sort')->get()->map(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price_xof' => $plan->price_xof,
            'currency' => $plan->currency,
            'features' => $plan->features,
            'quotas' => $plan->quotas,
            'is_active' => $plan->is_active,
            'sort' => $plan->sort,
            'companies_count' => (int) ($counts[$plan->id] ?? 0),
        ]);

        return response()->json(['data' => $plans], Response::HTTP_OK);
    }

    public function overQuota(): JsonResponse
    {
        $rows = $this->quotas->freeCompaniesOverQuota();

        return response()->json([
            'data' => array_map(fn (array $row) => [
                'company' => $this->summary($row['company']),
                'quotas' => $row['snapshot'],
            ], $rows),
            'meta' => ['total' => count($rows)],
        ], Response::HTTP_OK);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $this->detail($company)], Response::HTTP_OK);
    }

    public function assign(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $this->subscriptions->assignPlan(
            $company,
            $plan,
            isset($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : null,
            $request->user(),
            $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Plan attribué avec succès.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function renew(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'ends_at' => ['nullable', 'date', 'after:now'],
            'months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $endsAt = isset($validated['ends_at'])
            ? Carbon::parse($validated['ends_at'])
            : now()->addMonths((int) ($validated['months'] ?? 1));

        $this->subscriptions->renew($company, $endsAt, $request->user(), $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Abonnement renouvelé.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function extendTrial(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->subscriptions->extendTrial($company, (int) $validated['days'], $request->user());

        return response()->json([
            'message' => 'Période d’essai prolongée.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function resetTrial(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $this->subscriptions->resetTrial(
            $company,
            isset($validated['days']) ? (int) $validated['days'] : null,
            $request->user(),
        );

        return response()->json([
            'message' => 'Période d’essai réinitialisée.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function suspend(Request $request, Company $company): JsonResponse
    {
        if ((int) $request->user()->company_id === (int) $company->id) {
            throw ValidationException::withMessages([
                'company' => ['Vous ne pouvez pas suspendre votre propre entreprise.'],
            ]);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->subscriptions->suspend($company, $request->user(), $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Abonnement suspendu.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function reactivate(Request $request, Company $company): JsonResponse
    {
        $this->subscriptions->reactivate($company, $request->user());

        return response()->json([
            'message' => 'Abonnement réactivé.',
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function syncFreeze(Request $request, Company $company): JsonResponse
    {
        $stats = $this->subscriptions->syncFreezing($company);
        $this->subscriptions->log(
            $company,
            'subscription.freeze_sync',
            'Synchronisation manuelle des données gelées',
            ['stats' => $stats],
            $request->user(),
        );

        return response()->json([
            'message' => 'Gel des données resynchronisé.',
            'stats' => $stats,
            'data' => $this->detail($company->fresh()),
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Company $company): array
    {
        $company->loadMissing(['plan', 'activeSubscription.plan']);

        $frozenTotal = $this->quotas->frozenCount($company, 'users')
            + $this->quotas->frozenCount($company, 'products')
            + $this->quotas->frozenCount($company, 'invoices')
            + $this->quotas->frozenCount($company, 'warehouses');

        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'status' => $company->status,
            'trial_started_at' => $company->trial_started_at?->toIso8601String(),
            'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
            'trial_active' => $company->hasActiveTrial(),
            'suspended_at' => $company->suspended_at?->toIso8601String(),
            'plan' => $company->plan ? [
                'id' => $company->plan->id,
                'name' => $company->plan->name,
                'slug' => $company->plan->slug,
                'price_xof' => $company->plan->price_xof,
                'quotas' => $company->plan->quotas,
            ] : null,
            'subscription' => $this->subscriptionPayload($company->activeSubscription),
            'frozen_total' => $frozenTotal,
            'created_at' => $company->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Company $company): array
    {
        $company = $this->subscriptions->ensureCompanyIsUpToDate($company);
        $company->loadMissing(['plan']);

        $current = $this->subscriptions->currentSubscription($company);
        $latest = $this->subscriptions->latestSubscription($company);
        $snapshot = $this->quotas->snapshot($company);

        $history = Activity::where('company_id', $company->id)
            ->where(function ($query) {
                $query->where('event', 'like', 'subscription.%')
                    ->orWhere('description', 'like', '%abonnement%')
                    ->orWhere('description', 'like', '%essai%')
                    ->orWhere('description', 'like', '%plan%');
            })
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'created_at' => $activity->created_at?->toIso8601String(),
                'event' => $activity->event,
                'description' => $activity->description,
                'properties' => $activity->properties,
                'user' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
            ]);

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'email' => $company->metadata['email'] ?? null,
                'status' => $company->status,
                'trial_started_at' => $company->trial_started_at?->toIso8601String(),
                'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
                'trial_active' => $company->hasActiveTrial(),
                'suspended_at' => $company->suspended_at?->toIso8601String(),
                'created_at' => $company->created_at?->toIso8601String(),
            ],
            'plan' => $company->plan ? [
                'id' => $company->plan->id,
                'name' => $company->plan->name,
                'slug' => $company->plan->slug,
                'price_xof' => $company->plan->price_xof,
                'features' => $company->plan->features,
                'quotas' => $company->plan->quotas,
            ] : null,
            'subscription' => $this->subscriptionPayload($current),
            'latest_subscription' => $this->subscriptionPayload($latest),
            'quotas' => $snapshot,
            'frozen_records' => $this->quotas->frozenRecords($company),
            'history' => $history,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subscriptionPayload($subscription): ?array
    {
        if ($subscription === null) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'plan_name' => $subscription->plan?->name,
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'notes' => $subscription->notes,
            'created_at' => $subscription->created_at?->toIso8601String(),
        ];
    }
}
