<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Class TenantScope
 * Enforces company_id scoping on Eloquent queries.
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($companyId = TenantContext::getCompanyId()) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
