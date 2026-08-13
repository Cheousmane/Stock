<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CreditNote;
use App\Support\DashboardCache;

final class CreditNoteObserver
{
    public function created(CreditNote $credit_note): void
    {
        DashboardCache::forget($credit_note->company_id);
    }

    public function updated(CreditNote $credit_note): void
    {
        DashboardCache::forget($credit_note->company_id);
    }

    public function deleted(CreditNote $credit_note): void
    {
        DashboardCache::forget($credit_note->company_id);
    }
}
