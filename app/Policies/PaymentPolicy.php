<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->company_id === $payment->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_payment') || $user->hasRole('admin');
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($user->company_id !== $payment->company_id) {
            return false;
        }
        return $user->can('delete_payment') || $user->hasRole('admin');
    }
}
