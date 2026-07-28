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
use App\Models\DeliveryNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryNote::class);
        $deliveryNotes = DeliveryNote::query()
            ->with(['customer', 'items', 'invoice'])
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                });
            })
            ->when($status = $request->input('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->paginate($request->integer('per_page', 15));

        return response()->json(DeliveryNoteResource::collection($deliveryNotes), Response::HTTP_OK);
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
        $deliveryNote->load(['customer', 'items', 'invoice']);
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
