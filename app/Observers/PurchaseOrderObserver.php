<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Support\DashboardCache;

final class PurchaseOrderObserver
{
    public function created(PurchaseOrder $purchase_order): void
    {
        DashboardCache::forget($purchase_order->company_id);
    }

    public function updated(PurchaseOrder $purchase_order): void
    {
        DashboardCache::forget($purchase_order->company_id);
    }

    public function deleted(PurchaseOrder $purchase_order): void
    {
        DashboardCache::forget($purchase_order->company_id);
    }
}
