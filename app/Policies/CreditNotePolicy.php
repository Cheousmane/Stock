<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_credit_note');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->company_id === $creditNote->company_id && $user->can('view_credit_note');
    }

    public function create(User $user): bool
    {
        return $user->can('create_credit_note');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $user->company_id === $creditNote->company_id && $user->can('update_credit_note');
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->company_id === $creditNote->company_id && $user->can('delete_credit_note');
    }

    public function restore(User $user, CreditNote $creditNote): bool
    {
        return $user->company_id === $creditNote->company_id && $user->can('delete_credit_note');
    }

    public function forceDelete(User $user, CreditNote $creditNote): bool
    {
        return $user->company_id === $creditNote->company_id && $user->can('delete_credit_note');
    }
}
