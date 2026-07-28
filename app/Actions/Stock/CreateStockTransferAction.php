<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\DTOs\StockTransferDTO;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateStockTransferAction
{
    public function execute(StockTransferDTO $dto): StockTransfer
    {
        return DB::transaction(function () use ($dto) {
            $companyId = TenantContext::getCompanyId();

            $fromStock = WarehouseStock::where([
                'company_id' => $companyId,
                'warehouse_id' => $dto->fromWarehouseId,
                'product_id' => $dto->productId,
            ])->lockForUpdate()->firstOrFail();

            if ($fromStock->quantity < $dto->quantity) {
                throw new \RuntimeException(
                    "Insufficient stock in source warehouse. Available: {$fromStock->quantity}, requested: {$dto->quantity}."
                );
            }

            $toStock = WarehouseStock::firstOrCreate([
                'company_id' => $companyId,
                'warehouse_id' => $dto->toWarehouseId,
                'product_id' => $dto->productId,
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
            ]);

            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'from_warehouse_id' => $dto->fromWarehouseId,
                'to_warehouse_id' => $dto->toWarehouseId,
                'product_id' => $dto->productId,
                'quantity' => $dto->quantity,
                'status' => 'pending',
                'reason' => $dto->reason,
                'created_by' => auth()->id(),
            ]);

            $fromBefore = $fromStock->quantity;
            $fromAfter = $fromBefore - $dto->quantity;
            $fromStock->update([
                'quantity' => $fromAfter,
                'available_quantity' => max(0, $fromStock->available_quantity - $dto->quantity),
            ]);

            $toBefore = $toStock->quantity;
            $toAfter = $toBefore + $dto->quantity;
            $toStock->update([
                'quantity' => $toAfter,
                'available_quantity' => $toStock->available_quantity + $dto->quantity,
            ]);

            StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $dto->fromWarehouseId,
                'product_id' => $dto->productId,
                'type' => StockMovementType::Out,
                'quantity' => $dto->quantity,
                'before_quantity' => $fromBefore,
                'after_quantity' => $fromAfter,
                'reason' => "Stock transfer to warehouse #{$dto->toWarehouseId}: {$dto->reason}",
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'created_by' => auth()->id(),
            ]);

            StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $dto->toWarehouseId,
                'product_id' => $dto->productId,
                'type' => StockMovementType::In,
                'quantity' => $dto->quantity,
                'before_quantity' => $toBefore,
                'after_quantity' => $toAfter,
                'reason' => "Stock transfer from warehouse #{$dto->fromWarehouseId}: {$dto->reason}",
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'created_by' => auth()->id(),
            ]);

            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }
}
