<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_purchase_order');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->company_id === $purchaseOrder->company_id && $user->can('view_purchase_order');
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchase_order');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->company_id === $purchaseOrder->company_id && $user->can('update_purchase_order');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->company_id === $purchaseOrder->company_id && $user->can('delete_purchase_order');
    }

    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->company_id === $purchaseOrder->company_id && $user->can('delete_purchase_order');
    }

    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->company_id === $purchaseOrder->company_id && $user->can('delete_purchase_order');
    }
}
