<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockValuationResource;
use App\Http\Resources\StockValuationSummaryResource;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockValuationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockValuationController extends Controller
{
    public function __construct(
        private readonly StockValuationService $valuationService,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('view_stock');

        $warehouseId = $request->integer('warehouse_id');
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $summary = $this->valuationService->getValuationSummary($warehouse);

        return response()->json(
            new StockValuationSummaryResource($summary),
            Response::HTTP_OK,
        );
    }

    public function product(Product $product, Request $request): JsonResponse
    {
        $this->authorize('view_stock');

        $warehouseId = $request->integer('warehouse_id');
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
        $variantId = $request->integer('variant_id') ?: null;

        $valuations = $this->valuationService->getValuationForProduct($product, $warehouse, $variantId);

        return response()->json(
            StockValuationResource::collection($valuations),
            Response::HTTP_OK,
        );
    }

    public function averageCost(Product $product, Request $request): JsonResponse
    {
        $this->authorize('view_stock');

        $warehouse = Warehouse::findOrFail($request->integer('warehouse_id'));
        $avgCost = $this->valuationService->getWeightedAverageCost($product, $warehouse);

        return response()->json([
            'data' => [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'average_cost' => $avgCost,
            ],
        ], Response::HTTP_OK);
    }
}
