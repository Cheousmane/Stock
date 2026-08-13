<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageCompanyStatuses extends Command
{
    protected $signature = 'companies:manage-statuses';

    protected $description = 'Suspend automatiquement les essais expirés et réactive les suspensions à date échue.';

    public function handle(): int
    {
        // Réactiver les entreprises suspendues dont la date de fin est passée
        $companies = Company::where('status', 'suspended')
            ->whereNotNull('metadata->suspended_until')
            ->get();

        $reactivated = 0;
        foreach ($companies as $company) {
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

        // Suspendre les essais expirés
        $expiredCount = 0;
        Company::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->chunkById(100, function ($companies) use (&$expiredCount) {
                foreach ($companies as $company) {
                    DB::transaction(function () use ($company) {
                        $company->forceFill([
                            'status' => 'suspended',
                            'suspended_at' => now(),
                        ])->save();
                    });
                    $expiredCount++;
                    $this->info("Entreprise #{$company->id} ({$company->name}) suspendue (essai expiré).");
                }
            });

        $this->info("Terminé : {$reactivated} réactivation(s), {$expiredCount} suspension(s).");

        return self::SUCCESS;
    }
}