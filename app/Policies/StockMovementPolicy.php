<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock') || $user->hasRole('admin');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        if ($user->company_id !== $stockMovement->company_id) {
            return false;
        }
        return $user->can('view_stock') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('adjust_stock') || $user->hasRole('admin');
    }
}
