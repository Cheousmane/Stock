<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Company;
use App\Support\AdminDashboardCache;

final class CompanyObserver
{
    public function created(Company $company): void
    {
        AdminDashboardCache::bump();
    }

    public function updated(Company $company): void
    {
        AdminDashboardCache::bump();
    }

    public function deleted(Company $company): void
    {
        AdminDashboardCache::bump();
    }
}
