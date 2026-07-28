<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_product') || $user->hasRole('admin');
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->company_id !== $product->company_id) {
            return false;
        }
        return $user->can('update_product') || $user->hasRole('admin');
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->company_id !== $product->company_id) {
            return false;
        }
        return $user->can('delete_product') || $user->hasRole('admin');
    }
}
