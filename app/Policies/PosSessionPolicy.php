<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PosSession;
use App\Models\User;

class PosSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access_pos');
    }

    public function view(User $user, PosSession $session): bool
    {
        return $user->company_id === $session->company_id && $user->can('access_pos');
    }

    public function create(User $user): bool
    {
        return $user->can('access_pos');
    }

    public function update(User $user, PosSession $session): bool
    {
        return $user->company_id === $session->company_id && $user->can('access_pos');
    }
}
