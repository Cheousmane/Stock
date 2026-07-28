<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Payment\CreatePaymentAction;
use App\DTOs\PaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $payments = Payment::with('invoice')
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%");
                });
            })
            ->when($status = $request->input('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->paginate($request->integer('per_page', 15));

        return response()->json(PaymentResource::collection($payments), Response::HTTP_OK);
    }

    public function store(PaymentRequest $request, CreatePaymentAction $action): JsonResponse
    {
        $this->authorize('create', Payment::class);
        $dto = PaymentDTO::fromArray($request->validated());
        $payment = $action->execute($dto);
        return response()->json(new PaymentResource($payment->load('invoice')), Response::HTTP_CREATED);
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);
        $payment->load('invoice');
        return response()->json(new PaymentResource($payment), Response::HTTP_OK);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);
        $payment->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
