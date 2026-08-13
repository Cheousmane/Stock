<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Payment;
use App\Support\DashboardCache;

final class PaymentObserver
{
    public function created(Payment $payment): void
    {
        DashboardCache::forget($payment->company_id);
    }

    public function updated(Payment $payment): void
    {
        DashboardCache::forget($payment->company_id);
    }

    public function deleted(Payment $payment): void
    {
        DashboardCache::forget($payment->company_id);
    }
}
