<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\FrozenScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Données conservées en base mais désactivées lorsque le plan du tenant
 * ne permet plus de les exploiter.
 */
trait Freezable
{
    public static function bootFreezable(): void
    {
        static::addGlobalScope(new FrozenScope());
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    public function scopeWithFrozen(Builder $query): Builder
    {
        return $query->withoutGlobalScope(FrozenScope::class);
    }

    public function scopeOnlyFrozen(Builder $query): Builder
    {
        return $query->withoutGlobalScope(FrozenScope::class)->whereNotNull($this->getTable().'.frozen_at');
    }

    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();
        $casts['frozen_at'] = 'datetime';

        return $casts;
    }
}
