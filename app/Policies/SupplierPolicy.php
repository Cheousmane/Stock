<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_supplier');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id && $user->can('view_supplier');
    }

    public function create(User $user): bool
    {
        return $user->can('create_supplier') || $user->hasRole('admin');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id && ($user->can('update_supplier') || $user->hasRole('admin'));
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id && ($user->can('delete_supplier') || $user->hasRole('admin'));
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id && $user->can('delete_supplier');
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id && $user->can('delete_supplier');
    }
}
