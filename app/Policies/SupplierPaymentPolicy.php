<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupplierPayment;
use App\Models\User;

class SupplierPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierPayment $payment): bool
    {
        return $user->company_id === $payment->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_supplier_payment') || $user->hasRole('admin');
    }
}