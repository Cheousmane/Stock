<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->company_id === $warehouse->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_warehouse') || $user->hasRole('admin');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        if ($user->company_id !== $warehouse->company_id) {
            return false;
        }
        return $user->can('update_warehouse') || $user->hasRole('admin');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        if ($user->company_id !== $warehouse->company_id) {
            return false;
        }
        return $user->can('delete_warehouse') || $user->hasRole('admin');
    }
}
