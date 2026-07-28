<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_invoice') || $user->hasRole('admin');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }
        return $user->can('update_invoice') || $user->hasRole('admin');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->company_id !== $invoice->company_id) {
            return false;
        }
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }
        return $user->can('delete_invoice') || $user->hasRole('admin');
    }
}
