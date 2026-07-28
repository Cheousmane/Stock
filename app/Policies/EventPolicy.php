<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_events');
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->company_id !== $event->company_id) {
            return false;
        }
        return $user->can('view_events');
    }

    public function create(User $user): bool
    {
        return $user->can('view_events');
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->company_id !== $event->company_id) {
            return false;
        }
        return $user->can('view_events');
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->company_id !== $event->company_id) {
            return false;
        }
        return $user->can('view_events');
    }
}
