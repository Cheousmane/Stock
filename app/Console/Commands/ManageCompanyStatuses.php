<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageCompanyStatuses extends Command
{
    protected $signature = 'companies:manage-statuses';

    protected $description = 'Suspend les essais expirés, fait retomber les abonnements terminés sur l\'offre gratuite et réactive les suspensions à date échue.';

    public function handle(): int
    {
        // 1. Réactiver les entreprises suspendues dont la date de fin est passée
        $reactivated = 0;
        foreach (
            Company::where('status', 'suspended')
                ->whereNotNull('metadata->suspended_until')
                ->cursor() as $company
        ) {
            $suspendedUntil = $company->metadata['suspended_until'] ?? null;
            if ($suspendedUntil && now()->greaterThanOrEqualTo($suspendedUntil)) {
                $metadata = $company->metadata;
                unset($metadata['suspended_until']);
                DB::transaction(function () use ($company, $metadata) {
                    $company->forceFill([
                        'status' => 'active',
                        'suspended_at' => null,
                        'metadata' => $metadata,
                    ])->save();
                });
                $reactivated++;
                $this->info("Entreprise #{$company->id} ({$company->name}) réactivée automatiquement.");
            }
        }

        // 2. Essais expirés : sans abonnement valide => suspension, sinon normalisation.
        //    Plan gratuit choisi => retombée sur l'offre gratuite (pas de blocage).
        $suspended = 0;
        $freed = 0;
        $normalized = 0;
        Company::where('status', 'trial')
            ->where(function ($q) {
                $q->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', now())
                    // Filet : essai sans date mais créé il y a plus de 14 jours.
                    ->orWhere(function ($q) {
                        $q->whereNull('trial_ends_at')
                            ->where('created_at', '<', now()->subDays(14));
                    });
            })
            ->with('plan')
            ->chunkById(100, function ($companies) use (&$suspended, &$freed, &$normalized) {
                foreach ($companies as $company) {
                    $hasBillingSub = Subscription::where('company_id', $company->id)
                        ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                        ->exists();

                    DB::transaction(function () use ($company, $hasBillingSub, &$suspended, &$freed, &$normalized) {
                        if ($hasBillingSub) {
                            // Abonnement valide malgré un essai dépassé => compte actif.
                            $company->forceFill([
                                'status' => 'active',
                                'trial_ends_at' => null,
                            ])->save();
                            $normalized++;
                            $this->info("Entreprise #{$company->id} ({$company->name}) normalisée (abonnement valide).");
                        } elseif ($company->plan && $company->plan->slug === 'free') {
                            // Plan gratuit => offre gratuite permanente, sans blocage.
                            $company->forceFill([
                                'status' => 'active',
                                'trial_ends_at' => null,
                            ])->save();
                            $freed++;
                            $this->info("Entreprise #{$company->id} ({$company->name}) basculée sur l'offre gratuite.");
                        } else {
                            $company->forceFill([
                                'status' => 'suspended',
                                'suspended_at' => now(),
                            ])->save();
                            $suspended++;
                            $this->info("Entreprise #{$company->id} ({$company->name}) suspendue (essai expiré).");
                        }
                    });
                }
            });

        // 3. Abonnements annulés dont la période de grâce est terminée => retombée gratuite.
        $downgraded = 0;
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            $canceledIds = Subscription::where('stripe_status', 'canceled')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now())
                ->pluck('company_id')
                ->unique();
            foreach (Company::whereIn('id', $canceledIds)->where('status', 'active')->cursor() as $company) {
                $stillCovered = Subscription::where('company_id', $company->id)
                    ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                    ->exists();
                if ($stillCovered) {
                    continue;
                }
                $company->forceFill([
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'trial_ends_at' => null,
                ])->save();
                $downgraded++;
                $this->info("Entreprise #{$company->id} ({$company->name}) retombée sur l'offre gratuite.");
            }
        }

        $this->info("Terminé : {$reactivated} réactivation(s), {$suspended} suspension(s), {$freed} bascule(s) free, {$normalized} normalisation(s), {$downgraded} retombée(s).");

        return self::SUCCESS;
    }
}
