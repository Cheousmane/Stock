<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockValuation;
use App\Models\Warehouse;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockValuationService
{
    public function recordStockIn(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        int $unitCost,
        ?string $batchReference = null,
        ?int $variantId = null,
    ): StockValuation {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $unitCost, $batchReference, $variantId) {
            return StockValuation::create([
                'company_id' => TenantContext::getCompanyId(),
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'batch_reference' => $batchReference,
                'received_at' => now(),
            ]);
        });
    }

    public function recordStockOut(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        ?int $variantId = null,
    ): int {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $variantId) {
            $query = StockValuation::where('company_id', TenantContext::getCompanyId())
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->where('quantity', '>', 0);

            if ($variantId) {
                $query->where('product_variant_id', $variantId);
            } else {
                $query->whereNull('product_variant_id');
            }

            $layers = $query->orderBy('received_at')->orderBy('id')->get();

            $remaining = $quantity;
            $totalCost = 0;

            foreach ($layers as $layer) {
                if ($remaining <= 0) {
                    break;
                }

                $consumed = min($layer->quantity, $remaining);
                $layerCost = $consumed * $layer->unit_cost;
                $remaining -= $consumed;
                $totalCost += $layerCost;

                $layer->decrement('quantity', $consumed);
                $layer->decrement('total_cost', $layerCost);
            }

            return $totalCost;
        });
    }

    public function getValuationForProduct(
        Product $product,
        ?Warehouse $warehouse = null,
        ?int $variantId = null,
    ): Collection {
        $query = StockValuation::where('company_id', TenantContext::getCompanyId())
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0);

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        return $query->with(['warehouse', 'variant'])->orderBy('received_at')->get();
    }

    public function getValuationSummary(?Warehouse $warehouse = null): array
    {
        $query = StockValuation::where('company_id', TenantContext::getCompanyId())
            ->where('quantity', '>', 0);

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        $valuations = $query->get();

        $totalValue = $valuations->sum('total_cost');
        $totalQuantity = $valuations->sum('quantity');
        $productsCount = $valuations->groupBy('product_id')->count();

        $byWarehouse = $valuations->groupBy('warehouse_id')->map(function ($items) {
            return [
                'warehouse_id' => $items->first()->warehouse_id,
                'total_value' => $items->sum('total_cost'),
                'total_quantity' => $items->sum('quantity'),
                'products_count' => $items->groupBy('product_id')->count(),
            ];
        })->values();

        return [
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'products_count' => $productsCount,
            'valuation_method' => 'fifo',
            'by_warehouse' => $byWarehouse,
        ];
    }

    public function getWeightedAverageCost(
        Product $product,
        Warehouse $warehouse,
    ): ?int {
        $layers = StockValuation::where('company_id', TenantContext::getCompanyId())
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get();

        $totalQty = $layers->sum('quantity');
        $totalCost = $layers->sum('total_cost');

        if ($totalQty === 0) {
            return null;
        }

        return (int) round($totalCost / $totalQty);
    }
}
