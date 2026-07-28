<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\StockMovementType;
use App\Events\InvoiceCreated;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockValuationService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

final class DeductStockOnInvoiceCreated
{
    public function __construct(
        private readonly StockValuationService $valuationService,
    ) {}

    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $companyId = $invoice->company_id;

        $warehouse = Warehouse::where('company_id', $companyId)->first();

        $invoice->loadMissing('items.product');

        foreach ($invoice->items as $item) {
            if (!$item->product_id || !$item->product) {
                continue;
            }

            $product = $item->product;
            $quantity = (int) $item->quantity;
            $beforeQty = (int) ($product->quantity ?? 0);
            $afterQty = max(0, $beforeQty - $quantity);

            $stockMovement = StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse?->id,
                'product_id' => $product->id,
                'type' => StockMovementType::Out->value,
                'quantity' => $quantity,
                'before_quantity' => $beforeQty,
                'after_quantity' => $afterQty,
                'unit_cost' => null,
                'total_cost' => null,
                'reason' => 'Vente facture ' . $invoice->number,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'created_by' => null,
            ]);

            $product->decrement('quantity', $quantity);

            if ($warehouse) {
                $totalCost = $this->valuationService->recordStockOut(
                    $product,
                    $warehouse,
                    $quantity,
                );
                $stockMovement->update(['total_cost' => $totalCost]);
            }
        }
    }
}
