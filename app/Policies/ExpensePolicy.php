<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }
        return $user->can('view_expenses');
    }

    public function create(User $user): bool
    {
        return $user->can('view_expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }
        return $user->can('view_expenses');
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($user->company_id !== $expense->company_id) {
            return false;
        }
        return $user->can('view_expenses');
    }
}
