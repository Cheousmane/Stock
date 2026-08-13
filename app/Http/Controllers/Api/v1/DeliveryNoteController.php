<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\DeliveryNote\CreateDeliveryNoteAction;
use App\Actions\DeliveryNote\UpdateDeliveryNoteAction;
use App\Actions\DeliveryNote\UpdateDeliveryNoteStatusAction;
use App\DTOs\DeliveryNoteDTO;
use App\Enums\DeliveryNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryNoteRequest;
use App\Http\Resources\DeliveryNoteResource;
use App\Models\Customer;
use App\Models\DeliveryNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryNote::class);

        $sortable = [
            'number', 'customer_name', 'issue_date', 'delivery_date', 'status', 'created_at', 'id',
        ];
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'id';
        }

        $deliveryNotes = DeliveryNote::query()
            ->with(['customer:id,company_id,name,email,phone', 'invoice:id,company_id,number,total_xof'])
            ->withCount('items')
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status = $request->input('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($sortBy === 'customer_name', function ($query) use ($sortOrder) {
                $query->orderBy(Customer::select('name')->whereColumn('customers.id', 'delivery_notes.customer_id'), $sortOrder);
            }, function ($query) use ($sortBy, $sortOrder) {
                $query->orderBy($sortBy, $sortOrder);
            })
            ->paginate(min($request->integer('per_page', 15), 100));

        return DeliveryNoteResource::collection($deliveryNotes)->response();
    }

    public function store(DeliveryNoteRequest $request, CreateDeliveryNoteAction $action): JsonResponse
    {
        $this->authorize('create', DeliveryNote::class);
        $dto = DeliveryNoteDTO::fromArray($request->validated());
        $deliveryNote = $action->execute($dto);
        $deliveryNote->load(['customer', 'items', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_CREATED);
    }

    public function show(DeliveryNote $deliveryNote): JsonResponse
    {
        $this->authorize('view', $deliveryNote);
        $deliveryNote->load(['customer', 'items.product:id,name', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_OK);
    }

    public function update(DeliveryNoteRequest $request, DeliveryNote $deliveryNote, UpdateDeliveryNoteAction $action): JsonResponse
    {
        $this->authorize('update', $deliveryNote);
        $deliveryNote = $action->execute($deliveryNote, $request->validated());
        $deliveryNote->load(['customer', 'items', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_OK);
    }

    public function destroy(DeliveryNote $deliveryNote): JsonResponse
    {
        $this->authorize('delete', $deliveryNote);
        $deliveryNote->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', DeliveryNote::class);
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:delivery_notes,id',
        ]);
        $count = DeliveryNote::whereIn('id', $validated['ids'])->delete();

        return response()->json(['deleted' => $count], Response::HTTP_OK);
    }

    public function markAsShipped(DeliveryNote $deliveryNote, UpdateDeliveryNoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $deliveryNote);
        $deliveryNote = $action->execute($deliveryNote, DeliveryNoteStatus::Shipped);
        $deliveryNote->load(['customer', 'items', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_OK);
    }

    public function markAsDelivered(DeliveryNote $deliveryNote, UpdateDeliveryNoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $deliveryNote);
        $deliveryNote = $action->execute($deliveryNote, DeliveryNoteStatus::Delivered);
        $deliveryNote->load(['customer', 'items', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_OK);
    }

    public function markAsReturned(DeliveryNote $deliveryNote, UpdateDeliveryNoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $deliveryNote);
        $deliveryNote = $action->execute($deliveryNote, DeliveryNoteStatus::Returned);
        $deliveryNote->load(['customer', 'items', 'invoice']);
        return response()->json(new DeliveryNoteResource($deliveryNote), Response::HTTP_OK);
    }
}
