<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Purchases\CreatePurchaseOrderAction;
use App\Actions\Purchases\MarkPurchaseOrderAsReceivedAction;
use App\Actions\Purchases\UpdatePurchaseOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $purchaseOrders = PurchaseOrder::with(['supplier', 'warehouse'])
            ->latest()
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                });
            })
            ->when($status = $request->input('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->paginate($request->integer('per_page', 15));
        return response()->json($purchaseOrders);
    }

    public function store(PurchaseOrderRequest $request, CreatePurchaseOrderAction $action): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $purchaseOrder = $action->execute($request->validated());

        return response()->json($purchaseOrder->load('items', 'supplier', 'warehouse'), Response::HTTP_CREATED);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);
        return response()->json($purchaseOrder->load('items', 'supplier', 'warehouse'));
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, UpdatePurchaseOrderAction $action): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder = $action->execute($purchaseOrder, $request->validated());

        return response()->json($purchaseOrder->load('items', 'supplier', 'warehouse'), Response::HTTP_OK);
    }

    public function markAsReceived(PurchaseOrder $purchaseOrder, MarkPurchaseOrderAsReceivedAction $action): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);
        
        try {
            $po = $action->execute($purchaseOrder);
            return response()->json([
                'message' => 'Purchase order marked as received and stock updated.',
                'purchase_order' => $po
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('delete', $purchaseOrder);
        $purchaseOrder->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', PurchaseOrder::class);
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:purchase_orders,id',
        ]);
        $count = PurchaseOrder::whereIn('id', $validated['ids'])->delete();

        return response()->json(['deleted' => $count], Response::HTTP_OK);
    }
}
