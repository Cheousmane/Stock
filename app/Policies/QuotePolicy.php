<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->company_id === $quote->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_quote') || $user->hasRole('admin');
    }

    public function update(User $user, Quote $quote): bool
    {
        if ($user->company_id !== $quote->company_id) {
            return false;
        }
        return $user->can('update_quote') || $user->hasRole('admin');
    }

    public function delete(User $user, Quote $quote): bool
    {
        if ($user->company_id !== $quote->company_id) {
            return false;
        }
        return $user->can('delete_quote') || $user->hasRole('admin');
    }
}
