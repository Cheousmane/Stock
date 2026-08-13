<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Exception;

class ValidateCreditNoteAction
{
    public function execute(CreditNote $creditNote, ?int $warehouseIdForRestock = null): CreditNote
    {
        if ($creditNote->status !== 'draft') {
            throw new Exception("Only draft credit notes can be validated.");
        }

        $validated = null;

        DB::transaction(function () use ($creditNote, $warehouseIdForRestock, &$validated) {
            $companyId = TenantContext::getCompanyId();

            // Credit the customer's balance
            $customer = $creditNote->customer;
            $customer->balance_xof -= $creditNote->total_xof;
            $customer->save();

            // Restock items if a warehouse is provided
            if ($warehouseIdForRestock) {
                $restockedLines = 0;

                foreach ($creditNote->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    $stock = WarehouseStock::firstOrCreate(
                        [
                            'company_id' => $companyId,
                            'warehouse_id' => $warehouseIdForRestock,
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

                    StockMovement::create([
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseIdForRestock,
                        'product_id' => $item->product_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $afterQuantity,
                        'unit_cost' => $item->unit_price_xof,
                        'total_cost' => $item->total_xof,
                        'reason' => 'Credit Note Return',
                        'reference_type' => CreditNote::class,
                        'reference_id' => $creditNote->id,
                        'created_by' => auth()->id(),
                    ]);

                    $restockedLines++;
                }

                $metadata = $creditNote->metadata ?? [];
                $metadata['restock_warehouse_id'] = $warehouseIdForRestock;
                $metadata['restock_warehouse_name'] = Warehouse::find($warehouseIdForRestock)?->name;
                $metadata['restocked_lines'] = $restockedLines;
                $metadata['restocked_at'] = now()->toDateTimeString();
                $creditNote->update(['metadata' => $metadata]);
            }

            $creditNote->update(['status' => 'validated']);
            $validated = $creditNote->fresh();
        });

        return $validated;

        return $creditNote;
    }
}
