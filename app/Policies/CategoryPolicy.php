<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->company_id === $category->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_category') || $user->hasRole('admin');
    }

    public function update(User $user, Category $category): bool
    {
        if ($user->company_id !== $category->company_id) {
            return false;
        }
        return $user->can('update_category') || $user->hasRole('admin');
    }

    public function delete(User $user, Category $category): bool
    {
        if ($user->company_id !== $category->company_id) {
            return false;
        }
        return $user->can('delete_category') || $user->hasRole('admin');
    }
}
