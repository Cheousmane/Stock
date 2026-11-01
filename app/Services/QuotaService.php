<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Scopes\FrozenScope;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Source de vérité des quotas et de l'usage par entreprise.
 *
 * - Pendant l'essai actif : toutes les limites sont illimitées.
 * - Hors essai : les quotas du plan courant s'appliquent, y compris aux
 *   plans payants (un plan payant peut lui aussi avoir des limites).
 * - L'usage compte les données gelées : un downgrade ne "libère" donc pas
 *   artificiellement de la place.
 */
class QuotaService
{
    private const LABELS = [
        'users' => 'Utilisateurs',
        'products' => 'Produits',
        'invoices' => 'Factures',
        'warehouses' => 'Entrepôts',
    ];

    /**
     * @return array<string, string>
     */
    public function resourceMap(): array
    {
        return config('subscription.quota_resources', [
            'users' => 'max_users',
            'products' => 'max_products',
            'invoices' => 'max_invoices',
            'warehouses' => 'max_warehouses',
        ]);
    }

    public function resourceLabel(string $resource): string
    {
        return self::LABELS[$resource] ?? ucfirst($resource);
    }

    public function planFor(Company $company): ?Plan
    {
        if ($company->relationLoaded('plan')) {
            return $company->plan;
        }

        if ($company->plan_id !== null) {
            return Plan::find($company->plan_id);
        }

        return Plan::free();
    }

    /**
     * Limites effectives, indexées par ressource. -1 = illimité.
     *
     * @return array<string, int>
     */
    public function limitsFor(Company $company): array
    {
        $map = $this->resourceMap();
        $limits = [];

        if ($company->hasActiveTrial()) {
            foreach ($map as $resource => $quotaKey) {
                $limits[$resource] = -1;
            }

            return $limits;
        }

        $plan = $this->planFor($company);
        $fallback = config('subscription.fallback_free_quotas', []);

        foreach ($map as $resource => $quotaKey) {
            if ($plan !== null) {
                $limits[$resource] = $plan->quota($quotaKey);
            } else {
                $limits[$resource] = (int) ($fallback[$quotaKey] ?? -1);
            }
        }

        return $limits;
    }

    public function limitFor(Company $company, string $resource): int
    {
        return $this->limitsFor($company)[$resource] ?? -1;
    }

    /**
     * Nombre total de données, gelées comprises.
     */
    public function usage(Company $company, string $resource): int
    {
        return $this->queryFor($company, $resource)->count();
    }

    public function frozenCount(Company $company, string $resource): int
    {
        return $this->queryFor($company, $resource)
            ->whereNotNull($this->modelFor($resource)->getTable().'.frozen_at')
            ->count();
    }

    /**
     * Bloque la création si le quota effectif est atteint.
     */
    public function ensureCanCreate(Company $company, string $resource): void
    {
        $limit = $this->limitFor($company, $resource);

        if ($limit < 0) {
            return;
        }

        $used = $this->usage($company, $resource);

        if ($used >= $limit) {
            abort(Response::HTTP_FORBIDDEN, sprintf(
                'Quota atteint pour votre plan : %d %s maximum. Passez à un plan supérieur pour en ajouter.',
                $limit,
                strtolower($this->resourceLabel($resource)),
            ));
        }
    }

    /**
     * @return array{
     *     trial_active: bool,
     *     over_quota: bool,
     *     frozen_total: int,
     *     resources: array<int, array{
     *         resource: string,
     *         label: string,
     *         limit: int,
     *         used: int,
     *         frozen: int,
     *         remaining: int|null,
     *         over_quota: bool
     *     }>
     * }
     */
    public function snapshot(Company $company): array
    {
        $limits = $this->limitsFor($company);
        $resources = [];
        $frozenTotal = 0;
        $overQuota = false;

        foreach ($limits as $resource => $limit) {
            $used = $this->usage($company, $resource);
            $frozen = $this->frozenCount($company, $resource);
            $resourceOver = $limit >= 0 && $used > $limit;

            $resources[] = [
                'resource' => $resource,
                'label' => $this->resourceLabel($resource),
                'limit' => $limit,
                'used' => $used,
                'frozen' => $frozen,
                'remaining' => $limit < 0 ? null : max(0, $limit - $used),
                'over_quota' => $resourceOver,
            ];

            $frozenTotal += $frozen;
            $overQuota = $overQuota || $resourceOver;
        }

        return [
            'trial_active' => $company->hasActiveTrial(),
            'over_quota' => $overQuota,
            'frozen_total' => $frozenTotal,
            'resources' => $resources,
        ];
    }

    /**
     * Entreprises au plan gratuit dont l'usage dépasse les quotas.
     *
     * @return array<int, array{company: Company, snapshot: array}>
     */
    public function freeCompaniesOverQuota(): array
    {
        $freePlan = Plan::free();

        if ($freePlan === null) {
            return [];
        }

        $rows = [];

        Company::where('plan_id', $freePlan->id)
            ->where('status', '!=', 'trial')
            ->with('plan')
            ->orderBy('name')
            ->chunk(100, function ($companies) use (&$rows): void {
                foreach ($companies as $company) {
                    $snapshot = $this->snapshot($company);

                    if ($snapshot['over_quota']) {
                        $rows[] = [
                            'company' => $company,
                            'snapshot' => $snapshot,
                        ];
                    }
                }
            });

        return $rows;
    }

    /**
     * Détail des enregistrements gelés (pour le panneau Super Admin).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function frozenRecords(Company $company, int $limit = 100): array
    {
        $products = Product::withoutGlobalScope(FrozenScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('frozen_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'frozen_at'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->name,
                'reference' => $product->sku,
                'frozen_at' => $product->frozen_at?->toIso8601String(),
            ])->all();

        $invoices = Invoice::withoutGlobalScope(FrozenScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('frozen_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'number', 'total_xof', 'frozen_at'])
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'label' => $invoice->number,
                'reference' => $invoice->total_xof,
                'frozen_at' => $invoice->frozen_at?->toIso8601String(),
            ])->all();

        $warehouses = Warehouse::withoutGlobalScope(FrozenScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('frozen_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'frozen_at'])
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'label' => $warehouse->name,
                'reference' => $warehouse->code,
                'frozen_at' => $warehouse->frozen_at?->toIso8601String(),
            ])->all();

        $users = User::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotNull('frozen_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'frozen_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $user->name,
                'reference' => $user->email,
                'frozen_at' => $user->frozen_at?->toIso8601String(),
            ])->all();

        return [
            'products' => $products,
            'invoices' => $invoices,
            'warehouses' => $warehouses,
            'users' => $users,
        ];
    }

    private function queryFor(Company $company, string $resource)
    {
        $model = $this->modelFor($resource);

        if ($model instanceof User) {
            return User::withoutGlobalScopes()->where('company_id', $company->id);
        }

        return $model->newQuery()
            ->withoutGlobalScope(FrozenScope::class)
            ->where($model->getTable().'.company_id', $company->id);
    }

    private function modelFor(string $resource): Model
    {
        return match ($resource) {
            'users' => new User(),
            'products' => new Product(),
            'invoices' => new Invoice(),
            'warehouses' => new Warehouse(),
            default => throw new \InvalidArgumentException("Ressource de quota inconnue : {$resource}"),
        };
    }
}
