<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use App\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToTenant
 * Automatically applies TenantScope and sets company_id when saving models.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait to add the global TenantScope and listen to saving/creating events.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (empty($model->company_id) && TenantContext::has()) {
                $model->company_id = TenantContext::getCompanyId();
            }
        });
    }

    /**
     * Get the company that owns the model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
