<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Expense;
use App\Support\DashboardCache;

final class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        DashboardCache::forget($expense->company_id);
    }

    public function updated(Expense $expense): void
    {
        DashboardCache::forget($expense->company_id);
    }

    public function deleted(Expense $expense): void
    {
        DashboardCache::forget($expense->company_id);
    }
}
