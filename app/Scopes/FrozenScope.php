<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Exclut les données gelées des requêtes courantes (tenant).
 * Les écrans d'administration utilisent withoutGlobalScope(FrozenScope::class)
 * pour les inspecter et les dégeler.
 */
class FrozenScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->getTable().'.frozen_at');
    }
}
