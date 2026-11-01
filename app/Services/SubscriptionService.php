<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Scopes\FrozenScope;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gestion centralisée du cycle de vie des abonnements internes.
 *
 * Aucun paiement automatisé : le Super Admin attribue, renouvelle, suspend
 * ou réactive les abonnements. Toutes les transitions passent par ce service
 * afin de garder company, subscription, quotas et gel des données cohérents.
 */
class SubscriptionService
{
    public function __construct(private readonly QuotaService $quotas)
    {
    }

    public function trialDays(): int
    {
        return max(1, (int) config('subscription.trial_days', 14));
    }

    public function freePlan(): ?Plan
    {
        return Plan::free();
    }

    public function startTrial(Company $company, ?User $actor = null): Company
    {
        $free = $this->freePlan();
        $startedAt = now();

        $company->forceFill([
            'plan_id' => $free?->id,
            'status' => 'trial',
            'trial_started_at' => $startedAt,
            'trial_ends_at' => $startedAt->copy()->addDays($this->trialDays()),
            'suspended_at' => null,
        ])->save();

        $this->syncFreezing($company);

        $this->log($company, 'subscription.trial_started', 'Essai gratuit de '.$this->trialDays().' jours démarré', [
            'trial_started_at' => $company->trial_started_at?->toIso8601String(),
            'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
        ], $actor);

        return $company->fresh();
    }

    /**
     * Attribue un plan manuellement. Remplace l'abonnement actif éventuel.
     */
    public function assignPlan(
        Company $company,
        Plan $plan,
        ?CarbonInterface $endsAt = null,
        ?User $actor = null,
        ?string $notes = null,
    ): Company {
        $oldPlanId = $company->plan_id;

        DB::transaction(function () use ($company, $plan, $endsAt, $actor, $notes, $oldPlanId): void {
            $this->closeCurrentSubscriptions($company);

            if (! $plan->isFree()) {
                Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'type' => 'default',
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $endsAt ?? now()->addMonth(),
                    'created_by' => $actor?->id,
                    'notes' => $notes,
                ]);
            }

            $company->forceFill([
                'plan_id' => $plan->id,
                'status' => 'active',
                'suspended_at' => null,
                'trial_ends_at' => $company->trial_ends_at?->isFuture() ? now() : $company->trial_ends_at,
            ])->save();

            $this->syncFreezing($company);

            $this->log($company, 'subscription.assigned', 'Plan attribué : '.$plan->name, [
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $plan->id,
                'ends_at' => $endsAt?->toIso8601String(),
                'notes' => $notes,
            ], $actor);
        });

        return $company->fresh(['plan', 'activeSubscription.plan']);
    }

    public function renew(
        Company $company,
        ?CarbonInterface $endsAt = null,
        ?User $actor = null,
        ?string $notes = null,
    ): Company {
        $subscription = $this->currentSubscription($company);
        $plan = $subscription?->plan ?? $company->plan;

        if ($plan === null || $plan->isFree()) {
            throw ValidationException::withMessages([
                'plan_id' => ['Aucun abonnement payant à renouveler. Attribuez d’abord un plan.'],
            ]);
        }

        DB::transaction(function () use ($company, $subscription, $plan, $endsAt, $actor, $notes): void {
            $base = $endsAt === null
                ? ($subscription?->ends_at?->isFuture() ? $subscription->ends_at : now())
                : null;

            $newEnd = $endsAt ?? $base->copy()->addMonth();

            if ($subscription !== null) {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => $subscription->starts_at ?? now(),
                    'ends_at' => $newEnd,
                    'notes' => $notes ?? $subscription->notes,
                    'created_by' => $actor?->id ?? $subscription->created_by,
                ]);
            } else {
                Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'type' => 'default',
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $newEnd,
                    'created_by' => $actor?->id,
                    'notes' => $notes,
                ]);
            }

            $company->forceFill([
                'plan_id' => $plan->id,
                'status' => 'active',
                'suspended_at' => null,
                'trial_ends_at' => $company->trial_ends_at?->isFuture() ? now() : $company->trial_ends_at,
            ])->save();

            $this->syncFreezing($company);

            $this->log($company, 'subscription.renewed', 'Abonnement renouvelé jusqu’au '.$newEnd->format('d/m/Y'), [
                'plan_id' => $plan->id,
                'ends_at' => $newEnd->toIso8601String(),
                'notes' => $notes,
            ], $actor);
        });

        return $company->fresh(['plan', 'activeSubscription.plan']);
    }

    public function extendTrial(Company $company, int $days, ?User $actor = null): Company
    {
        $days = max(1, $days);
        $startedAt = $company->trial_started_at ?? now();
        $base = $company->trial_ends_at?->isFuture() ? $company->trial_ends_at : now();

        $company->forceFill([
            'status' => 'trial',
            'trial_started_at' => $startedAt,
            'trial_ends_at' => $base->copy()->addDays($days),
            'suspended_at' => null,
        ])->save();

        $this->syncFreezing($company);

        $this->log($company, 'subscription.trial_extended', 'Essai prolongé de '.$days.' jours', [
            'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
        ], $actor);

        return $company->fresh();
    }

    public function resetTrial(Company $company, ?int $days = null, ?User $actor = null): Company
    {
        $days = $days ?? $this->trialDays();
        $startedAt = now();

        $company->forceFill([
            'status' => 'trial',
            'trial_started_at' => $startedAt,
            'trial_ends_at' => $startedAt->copy()->addDays(max(1, $days)),
            'suspended_at' => null,
        ])->save();

        $this->syncFreezing($company);

        $this->log($company, 'subscription.trial_reset', 'Essai réinitialisé pour '.$days.' jours', [
            'trial_started_at' => $company->trial_started_at?->toIso8601String(),
            'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
        ], $actor);

        return $company->fresh();
    }

    public function suspend(Company $company, ?User $actor = null, ?string $notes = null): Company
    {
        DB::transaction(function () use ($company, $actor, $notes): void {
            Subscription::where('company_id', $company->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_SUSPENDED]);

            $company->forceFill([
                'status' => 'suspended',
                'suspended_at' => now(),
            ])->save();

            $this->log($company, 'subscription.suspended', 'Abonnement suspendu', [
                'notes' => $notes,
            ], $actor);
        });

        return $company->fresh();
    }

    public function reactivate(Company $company, ?User $actor = null): Company
    {
        DB::transaction(function () use ($company, $actor): void {
            $subscription = Subscription::where('company_id', $company->id)
                ->whereIn('status', [Subscription::STATUS_SUSPENDED, Subscription::STATUS_ACTIVE])
                ->latest('id')
                ->first();

            if ($subscription !== null && ($subscription->ends_at === null || $subscription->ends_at->isFuture())) {
                $subscription->update(['status' => Subscription::STATUS_ACTIVE]);
                $planId = $subscription->plan_id;
                $status = 'active';
            } elseif ($company->hasActiveTrial()) {
                $planId = $company->plan_id;
                $status = 'trial';
            } else {
                $planId = $this->freePlan()?->id;
                $status = 'active';
            }

            $company->forceFill([
                'plan_id' => $planId,
                'status' => $status,
                'suspended_at' => null,
            ])->save();

            $this->syncFreezing($company);

            $this->log($company, 'subscription.reactivated', 'Abonnement réactivé', [], $actor);
        });

        return $company->fresh(['plan', 'activeSubscription.plan']);
    }

    /**
     * Applique le gel/dégel des données selon le plan et l'essai en cours.
     *
     * @return array<string, array{used: int, frozen: int, limit: int}>
     */
    public function syncFreezing(Company $company): array
    {
        $company->refresh();

        $limits = $this->quotas->limitsFor($company);
        $stats = [];

        foreach ($limits as $resource => $limit) {
            $stats[$resource] = match ($resource) {
                'users' => $this->applyUsersQuota($company, $limit),
                default => $this->applyQuota($company, $resource, $limit),
            };
        }

        $company->clearPlanCache();

        return $stats;
    }

    /**
     * @return array{used: int, frozen: int, limit: int}
     */
    private function applyQuota(Company $company, string $resource, int $limit): array
    {
        $model = match ($resource) {
            'products' => new \App\Models\Product(),
            'invoices' => new \App\Models\Invoice(),
            'warehouses' => new \App\Models\Warehouse(),
            default => throw new \InvalidArgumentException("Ressource inconnue : {$resource}"),
        };

        $table = $model->getTable();
        $base = $model->newQuery()
            ->withoutGlobalScope(FrozenScope::class)
            ->where($table.'.company_id', $company->id);

        $used = (clone $base)->count();

        if ($limit < 0) {
            (clone $base)->whereNotNull($table.'.frozen_at')->update(['frozen_at' => null]);
            $frozen = 0;
        } else {
            $keepIds = (clone $base)->orderBy($table.'.id')->limit($limit)->pluck($table.'.id')->all();

            (clone $base)->whereNotIn($table.'.id', $keepIds)
                ->whereNull($table.'.frozen_at')
                ->update(['frozen_at' => now()]);

            (clone $base)->whereIn($table.'.id', $keepIds)
                ->whereNotNull($table.'.frozen_at')
                ->update(['frozen_at' => null]);

            $frozen = (clone $base)->whereNotNull($table.'.frozen_at')->count();
        }

        return ['used' => $used, 'frozen' => $frozen, 'limit' => $limit];
    }

    /**
     * Le propriétaire (utilisateur le plus ancien) n'est jamais gelé.
     *
     * @return array{used: int, frozen: int, limit: int}
     */
    private function applyUsersQuota(Company $company, int $limit): array
    {
        $base = User::withoutGlobalScopes()->where('company_id', $company->id);
        $used = (clone $base)->count();
        $ownerId = (clone $base)->orderBy('id')->value('id');

        if ($limit < 0) {
            (clone $base)->whereNotNull('frozen_at')->update(['frozen_at' => null]);

            return ['used' => $used, 'frozen' => 0, 'limit' => $limit];
        }

        $keepIds = [];
        if ($ownerId !== null) {
            $keepIds[] = $ownerId;

            if ($limit > 1) {
                $others = (clone $base)->where('id', '!=', $ownerId)
                    ->orderBy('id')
                    ->limit($limit - 1)
                    ->pluck('id')
                    ->all();
                $keepIds = array_merge($keepIds, $others);
            }
        }

        (clone $base)->whereNotIn('id', $keepIds)
            ->whereNull('frozen_at')
            ->update(['frozen_at' => now()]);

        (clone $base)->whereIn('id', $keepIds)
            ->whereNotNull('frozen_at')
            ->update(['frozen_at' => null]);

        $frozen = (clone $base)->whereNotNull('frozen_at')->count();

        return ['used' => $used, 'frozen' => $frozen, 'limit' => $limit];
    }

    public function expireDueTrials(): int
    {
        $count = 0;
        $days = $this->trialDays();

        Company::where('status', 'trial')
            ->where(function ($query) use ($days): void {
                $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<=', now())
                    ->orWhere(function ($query) use ($days): void {
                        $query->whereNull('trial_ends_at')->where('created_at', '<=', now()->subDays($days));
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($companies) use (&$count): void {
                foreach ($companies as $company) {
                    $this->expireCompanyTrial($company);
                    $count++;
                }
            });

        return $count;
    }

    public function expireCompanyTrial(Company $company): Company
    {
        $subscription = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($subscription !== null) {
            $company->forceFill([
                'plan_id' => $subscription->plan_id,
                'status' => 'active',
                'suspended_at' => null,
                'trial_ends_at' => $company->trial_ends_at ?? now(),
            ])->save();

            $this->log($company, 'subscription.trial_ended', 'Essai terminé : abonnement payant conservé', [
                'plan_id' => $subscription->plan_id,
            ]);
        } else {
            $free = $this->freePlan();

            $company->forceFill([
                'plan_id' => $free?->id,
                'status' => 'active',
                'suspended_at' => null,
                'trial_ends_at' => $company->trial_ends_at ?? now(),
            ])->save();

            $this->log($company, 'subscription.trial_ended', 'Essai terminé : passage au plan gratuit', [
                'plan_id' => $free?->id,
            ]);
        }

        $this->syncFreezing($company);

        return $company->fresh();
    }

    public function expireDueSubscriptions(): int
    {
        $count = 0;

        Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_SUSPENDED])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $company = $subscription->company;

                    $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

                    if ($company !== null && $this->downgradeIfUncovered($company)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Vérifie à la volée l'état d'une entreprise (trial/abonnement expiré).
     */
    public function ensureCompanyIsUpToDate(Company $company): Company
    {
        if ($company->isTrialExpired() || ($company->status === 'trial' && $company->trial_ends_at === null && $company->created_at?->lte(now()->subDays($this->trialDays())))) {
            return $this->expireCompanyTrial($company);
        }

        if (in_array($company->status, ['active', 'suspended'], true)) {
            $subscription = Subscription::where('company_id', $company->id)
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_SUSPENDED])
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', now())
                ->latest('id')
                ->first();

            if ($subscription !== null) {
                $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
                $this->downgradeIfUncovered($company);
            }
        }

        return $company->fresh(['plan', 'activeSubscription.plan']);
    }

    public function currentSubscription(Company $company): ?Subscription
    {
        return Subscription::where('company_id', $company->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_SUSPENDED])
            ->latest('id')
            ->first();
    }

    public function latestSubscription(Company $company): ?Subscription
    {
        return Subscription::where('company_id', $company->id)->latest('id')->first();
    }

    private function downgradeIfUncovered(Company $company): bool
    {
        if ($company->hasActiveTrial()) {
            return false;
        }

        $covered = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        if ($covered) {
            return false;
        }

        $free = $this->freePlan();

        $company->forceFill([
            'plan_id' => $free?->id,
            'status' => 'active',
            'suspended_at' => null,
        ])->save();

        $this->syncFreezing($company);

        $this->log($company, 'subscription.expired', 'Abonnement expiré : passage au plan gratuit', [
            'plan_id' => $free?->id,
        ]);

        return true;
    }

    private function closeCurrentSubscriptions(Company $company): void
    {
        Subscription::where('company_id', $company->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_SUSPENDED])
            ->update([
                'status' => Subscription::STATUS_EXPIRED,
                'ends_at' => now(),
            ]);
    }

    public function log(
        Company $company,
        string $event,
        string $description,
        array $properties = [],
        ?User $actor = null,
    ): void {
        activity()
            ->causedBy($actor ?? auth()->user())
            ->performedOn($company)
            ->event($event)
            ->withProperties($properties)
            ->tap(function ($activity) use ($company): void {
                $activity->company_id = $company->id;
            })
            ->log($description);
    }
}
