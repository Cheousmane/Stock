<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Purchases\CreateSupplierPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierPaymentRequest;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupplierPayment::class);

        $payments = SupplierPayment::with(['supplier:id,company_id,name,code', 'purchaseOrder:id,company_id,number'])
            ->latest()
            ->when($supplierId = $request->integer('supplier_id'), function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($poId = $request->integer('purchase_order_id'), function ($query) use ($poId) {
                $query->where('purchase_order_id', $poId);
            })
            ->paginate($request->integer('per_page', 15));

        return response()->json($payments, Response::HTTP_OK);
    }

    public function store(SupplierPaymentRequest $request, CreateSupplierPaymentAction $action): JsonResponse
    {
        $this->authorize('create', SupplierPayment::class);

        $payment = $action->execute($request->validated());

        return response()->json(
            $payment->load('supplier:id,company_id,name,code', 'purchaseOrder:id,company_id,number'),
            Response::HTTP_CREATED
        );
    }
}