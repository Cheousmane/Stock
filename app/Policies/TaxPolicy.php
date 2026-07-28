<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tax;
use App\Models\User;

class TaxPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->company_id === $tax->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_tax') || $user->hasRole('admin');
    }

    public function update(User $user, Tax $tax): bool
    {
        if ($user->company_id !== $tax->company_id) {
            return false;
        }
        return $user->can('update_tax') || $user->hasRole('admin');
    }

    public function delete(User $user, Tax $tax): bool
    {
        if ($user->company_id !== $tax->company_id) {
            return false;
        }
        return $user->can('delete_tax') || $user->hasRole('admin');
    }
}
