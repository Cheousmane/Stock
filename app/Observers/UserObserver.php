<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Support\AdminDashboardCache;

final class UserObserver
{
    public function created(User $user): void
    {
        AdminDashboardCache::bump();
    }

    public function updated(User $user): void
    {
        AdminDashboardCache::bump();
    }

    public function deleted(User $user): void
    {
        AdminDashboardCache::bump();
    }
}
