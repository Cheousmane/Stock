<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_product_variant') || $user->hasRole('admin');
    }

    public function view(User $user, ProductVariant $productVariant): bool
    {
        if ($user->company_id !== $productVariant->company_id) {
            return false;
        }
        return $user->can('view_product_variant') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_variant') || $user->hasRole('admin');
    }

    public function update(User $user, ProductVariant $productVariant): bool
    {
        if ($user->company_id !== $productVariant->company_id) {
            return false;
        }
        return $user->can('update_product_variant') || $user->hasRole('admin');
    }

    public function delete(User $user, ProductVariant $productVariant): bool
    {
        if ($user->company_id !== $productVariant->company_id) {
            return false;
        }
        return $user->can('delete_product_variant') || $user->hasRole('admin');
    }
}
