<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SyncProductStockQuantityJob;
use App\Models\WarehouseStock;
use App\Support\DashboardCache;

final class WarehouseStockObserver
{
    public function saved(WarehouseStock $warehouseStock): void
    {
        DashboardCache::forget($warehouseStock->company_id);

        if ($warehouseStock->product_id) {
            SyncProductStockQuantityJob::dispatch($warehouseStock->product_id);
        }
    }

    public function deleted(WarehouseStock $warehouseStock): void
    {
        DashboardCache::forget($warehouseStock->company_id);

        if ($warehouseStock->product_id) {
            SyncProductStockQuantityJob::dispatch($warehouseStock->product_id);
        }
    }
}
