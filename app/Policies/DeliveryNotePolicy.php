<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DeliveryNote;
use App\Models\User;

class DeliveryNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->company_id === $deliveryNote->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_delivery_note') || $user->hasRole('admin');
    }

    public function update(User $user, DeliveryNote $deliveryNote): bool
    {
        if ($user->company_id !== $deliveryNote->company_id) {
            return false;
        }
        return $user->can('update_delivery_note') || $user->hasRole('admin');
    }

    public function delete(User $user, DeliveryNote $deliveryNote): bool
    {
        if ($user->company_id !== $deliveryNote->company_id) {
            return false;
        }
        return $user->can('delete_delivery_note') || $user->hasRole('admin');
    }
}
