<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_customer') || $user->hasRole('admin');
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->company_id !== $customer->company_id) {
            return false;
        }
        return $user->can('update_customer') || $user->hasRole('admin');
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user->company_id !== $customer->company_id) {
            return false;
        }
        return $user->can('delete_customer') || $user->hasRole('admin');
    }
}
