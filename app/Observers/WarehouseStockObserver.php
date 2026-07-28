<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SyncProductStockQuantityJob;
use App\Models\WarehouseStock;

final class WarehouseStockObserver
{
    public function saved(WarehouseStock $warehouseStock): void
    {
        if ($warehouseStock->product_id) {
            SyncProductStockQuantityJob::dispatch($warehouseStock->product_id);
        }
    }

    public function deleted(WarehouseStock $warehouseStock): void
    {
        if ($warehouseStock->product_id) {
            SyncProductStockQuantityJob::dispatch($warehouseStock->product_id);
        }
    }
}
