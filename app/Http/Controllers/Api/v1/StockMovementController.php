<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockValuationService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockValuationService $valuationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);
        $movements = StockMovement::with('product')
            ->orderByDesc('created_at')
            ->limit($request->integer('limit', 20))
            ->get();

        return response()->json(StockMovementResource::collection($movements), Response::HTTP_OK);
    }

    public function store(StockMovementRequest $request): JsonResponse
    {
        $this->authorize('create', StockMovement::class);

        $product = Product::findOrFail($request->input('product_id'));
        $quantity = $request->integer('quantity');
        $type = $request->input('type');

        $companyId = TenantContext::getCompanyId();
        $warehouseId = $request->input('warehouse_id');

        if ($warehouseId) {
            $warehouse = Warehouse::findOrFail($warehouseId);
        } else {
            $warehouse = Warehouse::where('company_id', $companyId)->first();
            if (!$warehouse) {
                $warehouse = Warehouse::create([
                    'company_id' => $companyId,
                    'name' => 'Entrepôt principal',
                    'code' => 'PRINCIPAL',
                    'is_active' => true,
                ]);
            }
            $warehouseId = $warehouse->id;
        }

        $stock = WarehouseStock::firstOrCreate([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ], ['quantity' => 0, 'available_quantity' => 0]);

        $beforeQty = $stock->quantity;
        $afterQty = $type === 'in' ? $beforeQty + $quantity : $beforeQty - $quantity;

        if ($type === 'out' && $afterQty < 0) {
            return response()->json([
                'message' => 'Stock insuffisant pour cette sortie.',
                'errors' => ['quantity' => ['La quantité en stock est insuffisante.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $unitCost = $request->integer('unit_cost') ?: $product->purchase_price_xof ?? $product->cost_price_xof ?? 0;

        $stock->quantity = $afterQty;
        if ($type === 'in') {
            $stock->available_quantity += $quantity;
        } elseif ($type === 'out') {
            $stock->available_quantity = max(0, $stock->available_quantity - $quantity);
        }
        $stock->save();
        $stock->refresh();

        $movement = StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => $stock->quantity,
            'unit_cost' => $type === 'in' ? $unitCost : null,
            'total_cost' => $type === 'in' ? $quantity * $unitCost : null,
            'reason' => $request->input('reason'),
            'created_by' => $request->user()?->id,
        ]);

        if ($type === 'in') {
            $this->valuationService->recordStockIn(
                $product, $warehouse, $quantity, $unitCost,
                batchReference: $request->input('reason'),
            );
        } elseif ($type === 'out') {
            $totalCost = $this->valuationService->recordStockOut($product, $warehouse, $quantity);
            $movement->update(['total_cost' => $totalCost]);
        }

        return response()->json(
            new StockMovementResource($movement->load('product')),
            Response::HTTP_CREATED,
        );
    }
}
