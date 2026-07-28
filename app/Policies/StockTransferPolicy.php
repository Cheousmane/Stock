<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stock') || $user->hasRole('admin');
    }

    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        if ($user->company_id !== $stockTransfer->company_id) {
            return false;
        }
        return $user->can('view_stock') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('transfer_stock') || $user->hasRole('admin');
    }
}
