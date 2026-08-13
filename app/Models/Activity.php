<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TenantContext;

/**
 * Modèle Activity personnalisé : rattache automatiquement chaque log
 * à l'entreprise active (TenantContext) afin que chaque utilisateur
 * ne voie que les modifications concernant sa propre application.
 */
class Activity extends \Spatie\Activitylog\Models\Activity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if ($activity->company_id === null && ($companyId = TenantContext::getCompanyId())) {
                $activity->company_id = $companyId;
            }
        });
    }
}
