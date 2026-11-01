<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ManageCompanyStatuses extends Command
{
    protected $signature = 'companies:manage-statuses';

    protected $description = 'Applique les fins d’essai/abonnement, le retour au plan gratuit, le gel des excédents et les réactivations programmées.';

    public function handle(SubscriptionService $subscriptions): int
    {
        // 1. Réactiver les entreprises suspendues dont la date de fin est passée.
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

                $subscriptions->reactivate($company);
                $company->forceFill(['metadata' => $metadata])->save();

                $reactivated++;
                $this->info("Entreprise #{$company->id} ({$company->name}) réactivée automatiquement.");
            }
        }

        // 2. Essais expirés => plan gratuit (ou abonnement payant encore actif)
        //    puis application des quotas et gel des excédents.
        $trials = $subscriptions->expireDueTrials();

        // 3. Abonnements arrivés à échéance => plan gratuit + gel.
        $expired = $subscriptions->expireDueSubscriptions();

        $this->info("Terminé : {$reactivated} réactivation(s), {$trials} essai(s) expiré(s), {$expired} abonnement(s) expiré(s).");

        return self::SUCCESS;
    }
}
