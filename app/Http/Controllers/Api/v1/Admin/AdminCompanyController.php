<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Enums\InvoiceStatus;
use App\Exports\AdminCompaniesExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCompanyResource;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Scopes\FrozenScope;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Maatwebsite\Excel\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class AdminCompanyController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly QuotaService $quotas,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $sortable = ['name', 'slug', 'email', 'status', 'size', 'plan_id', 'created_at', 'id'];
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
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('size'), function ($query, $size) {
                $query->where('size', $size);
            })
            ->when($request->input('plan_id'), function ($query, $planId) {
                $query->where('plan_id', $planId);
            })
            ->when($request->input('date_from'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->input('date_to'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->orderBy($sort, $order)
            ->paginate(min($request->integer('per_page', 20), 100));

        return AdminCompanyResource::collection($companies)->response();
    }

    public function show(Company $company): JsonResponse
    {
        $company = $this->subscriptions->ensureCompanyIsUpToDate($company);
        $company->load(['plan', 'users', 'activeSubscription.plan']);
        $company->loadCount(['users', 'invoices', 'customers', 'products']);

        $snapshot = $this->quotas->snapshot($company);

        // Le panneau Super Admin doit voir toutes les données, y compris gelées.
        $company->users_count = $snapshot['resources'][0]['used'] ?? $company->users_count;
        foreach ($snapshot['resources'] as $resource) {
            if ($resource['resource'] === 'products') {
                $company->products_count = $resource['used'];
            }
            if ($resource['resource'] === 'invoices') {
                $company->invoices_count = $resource['used'];
            }
        }

        $lastLogin = $company->users()
            ->whereNotNull('last_login_at')
            ->max('last_login_at');

        $company->last_login = $lastLogin;
        $company->owner = $company->users->sortBy('id')->first();

        $invoicedVolume30d = (int) Invoice::withoutGlobalScope(FrozenScope::class)
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_xof');

        $invoicesCount30d = (int) Invoice::withoutGlobalScope(FrozenScope::class)
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $company->invoiced_volume_30d = $invoicedVolume30d;
        $company->invoices_count_30d = $invoicesCount30d;

        $subscription = $this->subscriptions->currentSubscription($company);
        $company->subscription = $subscription ? [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'plan_name' => $subscription->plan?->name,
            'plan_id' => $subscription->plan_id,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'notes' => $subscription->notes,
            'created_at' => $subscription->created_at,
        ] : null;
        $company->quota_snapshot = $snapshot;
        $company->frozen_records = $this->quotas->frozenRecords($company);

        $timeline = Activity::with('causer')
            ->where('company_id', $company->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'created_at' => $a->created_at,
                'description' => $a->description,
                'event' => $a->event,
                'log_name' => $a->log_name,
                'user' => $a->causer ? [
                    'name' => $a->causer->name,
                    'email' => $a->causer->email,
                ] : null,
            ]);

        $company->timeline = $timeline;

        return response()->json(new AdminCompanyResource($company), Response::HTTP_OK);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,suspended,trial,disabled'],
            'size' => ['sometimes', 'nullable', 'string', 'max:50'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string'],
            'plan_id' => ['sometimes', 'nullable', 'exists:plans,id'],
            'trial_started_at' => ['sometimes', 'nullable', 'date'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'suspended_until' => ['sometimes', 'nullable', 'date'],
        ]);

        $newPlanId = $validated['plan_id'] ?? null;
        unset($validated['plan_id']);

        if (isset($validated['status'])) {
            $validated['suspended_at'] = $validated['status'] === 'suspended' ? now() : null;
        }

        $metadata = $company->metadata ?? [];
        foreach (['email', 'phone', 'address'] as $field) {
            if (array_key_exists($field, $validated)) {
                $metadata[$field] = $validated[$field];
                unset($validated[$field]);
            }
        }
        if (array_key_exists('suspended_until', $validated)) {
            if ($validated['suspended_until'] === null) {
                unset($metadata['suspended_until']);
            } else {
                $metadata['suspended_until'] = $validated['suspended_until'];
            }
            unset($validated['suspended_until']);
        }
        if ($metadata !== ($company->metadata ?? [])) {
            $validated['metadata'] = $metadata;
        }

        $company->update($validated);

        if ($newPlanId !== null && (int) $newPlanId !== (int) $company->plan_id) {
            $this->subscriptions->assignPlan(
                $company->fresh(),
                Plan::findOrFail($newPlanId),
                null,
                $request->user(),
            );
        }

        return response()->json(new AdminCompanyResource($company->fresh(['plan', 'activeSubscription.plan'])), Response::HTTP_OK);
    }

    public function suspend(Request $request, Company $company): JsonResponse
    {
        $this->subscriptions->suspend($company, $request->user());

        return response()->json([
            'message' => 'Entreprise suspendue.',
            'company' => new AdminCompanyResource($company->fresh()),
        ], Response::HTTP_OK);
    }

    public function activate(Request $request, Company $company): JsonResponse
    {
        $this->subscriptions->reactivate($company, $request->user());

        $metadata = $company->fresh()->metadata ?? [];
        unset($metadata['suspended_until']);
        $company->forceFill(['metadata' => $metadata])->save();

        return response()->json([
            'message' => 'Entreprise activée.',
            'company' => new AdminCompanyResource($company->fresh(['plan', 'activeSubscription.plan'])),
        ], Response::HTTP_OK);
    }

    /**
     * Suppression définitive d'une entreprise et de toutes ses données.
     * Irréversible : les tables liées sont purgées via les cascades DB,
     * les orphelins (tokens, rôles, logs, fichiers) sont nettoyés ici.
     */
    public function destroy(Request $request, Company $company): JsonResponse
    {
        if ((int) $request->user()->company_id === (int) $company->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre entreprise.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $companyName = $company->name;
        $stats = [
            'users' => $company->users()->count(),
            'invoices' => $company->invoices()->withoutGlobalScope(FrozenScope::class)->count(),
            'customers' => $company->customers()->count(),
            'products' => $company->products()->withoutGlobalScope(FrozenScope::class)->count(),
        ];

        // Fichiers à purger (best effort, hors transaction).
        $files = [];
        $logo = $company->metadata['logo'] ?? null;
        if (is_string($logo) && $logo !== '') {
            $files[] = $logo;
        }
        foreach ($company->products()->withoutGlobalScope(FrozenScope::class)->whereNotNull('image')->pluck('image') as $image) {
            if (is_string($image) && ! str_starts_with($image, 'http')) {
                $files[] = $image;
            }
        }

        DB::transaction(function () use ($company) {
            $userIds = $company->users()->pluck('id');

            // Tokens d'API (table polymorphe, sans FK).
            PersonalAccessToken::where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            // Rôles de l'entreprise (les pivots cascadent via les FK).
            Role::where('company_id', $company->id)->delete();

            // Permissions directes (team key = entreprise).
            $teamKey = config('permission.column_names.team_foreign_key', 'company_id');
            DB::table('model_has_permissions')->where($teamKey, $company->id)->delete();

            // Journal d'activité de l'entreprise.
            DB::table('activity_log')->where('company_id', $company->id)->delete();

            // Le reste (factures, clients, produits, stocks, caisse, abonnements...)
            // est purgé par les `cascadeOnDelete` de la base.
            $company->clearPlanCache();
            $company->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_unique($files) as $file) {
            try {
                Storage::disk('public')->delete($file);
            } catch (\Throwable) {
            }
        }

        activity()
            ->causedBy($request->user())
            ->event('deleted')
            ->withProperties([
                'company_id' => $company->id,
                'company_name' => $companyName,
                'stats' => $stats,
            ])
            ->log('Entreprise '.$companyName.' supprimée définitivement par superadmin');

        return response()->json([
            'message' => 'Entreprise supprimée définitivement.',
            'stats' => $stats,
        ], Response::HTTP_OK);
    }

    public function assignPlan(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $this->subscriptions->assignPlan(
            $company,
            $plan,
            isset($validated['ends_at']) ? \Illuminate\Support\Carbon::parse($validated['ends_at']) : null,
            $request->user(),
            $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Plan assigné avec succès.',
            'company' => new AdminCompanyResource($company->fresh(['plan', 'activeSubscription.plan'])),
        ], Response::HTTP_OK);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
            'trial' => Company::where('status', 'trial')->count(),
            'disabled' => Company::where('status', 'disabled')->count(),
            'free' => Company::whereHas('plan', fn ($q) => $q->where('slug', Plan::FREE_SLUG))->count(),
            'paid' => Company::whereHas('plan', fn ($q) => $q->where('slug', '!=', Plan::FREE_SLUG))->count(),
            'over_quota' => count($this->quotas->freeCompaniesOverQuota()),
            'by_size' => Company::whereNotNull('size')
                ->selectRaw('size, COUNT(*) as count')
                ->groupBy('size')
                ->pluck('count', 'size'),
            'by_industry' => Company::whereNotNull('industry')
                ->selectRaw('industry, COUNT(*) as count')
                ->groupBy('industry')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'industry'),
            'registrations_last_30_days' => Company::where('created_at', '>=', now()->subDays(30))->count(),
            'registrations_last_7_days' => Company::where('created_at', '>=', now()->subDays(7))->count(),
            'plans' => Plan::orderBy('sort')->get(['id', 'name', 'slug', 'price_xof', 'is_active', 'quotas', 'features']),
        ];

        return response()->json($stats, Response::HTTP_OK);
    }

    /**
     * Export companies matching the same filters as index().
     */
    public function export(Request $request)
    {
        $companies = Company::with(['plan', 'activeSubscription.plan'])
            ->withCount(['users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('size'), function ($query, $size) {
                $query->where('size', $size);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new AdminCompaniesExport($companies),
            'entreprises-'.date('Y-m-d-His').'.csv',
            Excel::CSV
        );
    }
}
