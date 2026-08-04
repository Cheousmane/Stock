<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\UpdateInvoiceAction;
use App\Actions\Invoice\UpdateInvoiceStatusAction;
use App\DTOs\InvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $perPage = (int) $request->input('per_page', 15);
        $query = Invoice::with(['customer', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $statuses = array_map('trim', explode(',', $status));
            $query->whereIn('status', $statuses);
        }

        if ($limit = $request->integer('limit')) {
            $limit = min($limit, 100);
            return response()->json(InvoiceResource::collection($query->limit($limit)->get()), Response::HTTP_OK);
        }

        return response()->json(InvoiceResource::collection($query->paginate($perPage)), Response::HTTP_OK);
    }

    public function store(InvoiceRequest $request, CreateInvoiceAction $action): JsonResponse
    {
        $this->authorize('create', Invoice::class);
        $dto = InvoiceDTO::fromArray($request->validated());
        $invoice = $action->execute($dto);
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_CREATED);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
    }

    public function update(InvoiceRequest $request, Invoice $invoice, UpdateInvoiceAction $action): JsonResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $action->execute($invoice, $request->validated());
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Suppression groupée de factures (utilisee par le frontend).
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', Invoice::class);
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:invoices,id']);
        $count = Invoice::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $count], Response::HTTP_OK);
    }

    public function markAsSent(Invoice $invoice, UpdateInvoiceStatusAction $action): JsonResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $action->execute($invoice, InvoiceStatus::Sent);
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
    }

    public function markAsCancelled(Invoice $invoice, UpdateInvoiceStatusAction $action): JsonResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $action->execute($invoice, InvoiceStatus::Cancelled);
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_OK);
    }
}
