<?php

declare(strict_types=1);

namespace App\Actions\Purchases;

use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Exception;

class MarkPurchaseOrderAsReceivedAction
{
    public function execute(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if ($purchaseOrder->status === 'received') {
            throw new Exception("This purchase order is already marked as received.");
        }

        if (!$purchaseOrder->warehouse_id) {
            throw new Exception("A warehouse must be specified to receive stock.");
        }

        DB::transaction(function () use ($purchaseOrder) {
            $companyId = TenantContext::getCompanyId();
            $warehouseId = $purchaseOrder->warehouse_id;

            foreach ($purchaseOrder->items as $item) {
                // Update or create warehouse stock
                $stock = WarehouseStock::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'available_quantity' => 0,
                    ]
                );

                $beforeQuantity = $stock->quantity;
                $afterQuantity = $beforeQuantity + $item->quantity;

                $stock->update([
                    'quantity' => $afterQuantity,
                    'available_quantity' => $stock->available_quantity + $item->quantity,
                ]);

                // Create stock movement
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'unit_cost' => $item->unit_price_xof,
                    'total_cost' => $item->total_xof,
                    'reason' => 'Purchase Order Received',
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $purchaseOrder->id,
                    'created_by' => auth()->id(),
                ]);
            }

            $purchaseOrder->update(['status' => 'received']);

            if ($purchaseOrder->total_xof > 0) {
                Expense::create([
                    'company_id' => $companyId,
                    'description' => 'Bon de commande reçu : ' . $purchaseOrder->number,
                    'category' => 'purchase',
                    'amount' => $purchaseOrder->total_xof,
                    'date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return $purchaseOrder;
    }
}
