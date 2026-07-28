<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEndpoint;

class WebhookEndpointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_settings') || $user->hasRole('admin');
    }

    public function view(User $user, WebhookEndpoint $endpoint): bool
    {
        if ($user->company_id !== $endpoint->company_id) {
            return false;
        }
        return $user->can('manage_settings') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_settings') || $user->hasRole('admin');
    }

    public function update(User $user, WebhookEndpoint $endpoint): bool
    {
        if ($user->company_id !== $endpoint->company_id) {
            return false;
        }
        return $user->can('manage_settings') || $user->hasRole('admin');
    }

    public function delete(User $user, WebhookEndpoint $endpoint): bool
    {
        if ($user->company_id !== $endpoint->company_id) {
            return false;
        }
        return $user->can('manage_settings') || $user->hasRole('admin');
    }
}
