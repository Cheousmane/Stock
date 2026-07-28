<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->company_id === $unit->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_unit') || $user->hasRole('admin');
    }

    public function update(User $user, Unit $unit): bool
    {
        if ($user->company_id !== $unit->company_id) {
            return false;
        }
        return $user->can('update_unit') || $user->hasRole('admin');
    }

    public function delete(User $user, Unit $unit): bool
    {
        if ($user->company_id !== $unit->company_id) {
            return false;
        }
        return $user->can('delete_unit') || $user->hasRole('admin');
    }
}
