<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Sales\CreateCreditNoteAction;
use App\Actions\Sales\UpdateCreditNoteAction;
use App\Actions\Sales\ValidateCreditNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreditNoteRequest;
use App\Http\Resources\CreditNoteResource;
use App\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreditNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CreditNote::class);
        $creditNotes = CreditNote::with(['customer', 'invoice'])
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
        return response()->json(CreditNoteResource::collection($creditNotes));
    }

    public function store(CreditNoteRequest $request, CreateCreditNoteAction $action): JsonResponse
    {
        $this->authorize('create', CreditNote::class);

        $creditNote = $action->execute($request->validated());

        return response()->json(new CreditNoteResource($creditNote->load('items', 'customer', 'invoice')), Response::HTTP_CREATED);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        $this->authorize('view', $creditNote);
        return response()->json(new CreditNoteResource($creditNote->load('items', 'customer', 'invoice')));
    }

    public function update(CreditNoteRequest $request, CreditNote $creditNote, UpdateCreditNoteAction $action): JsonResponse
    {
        $this->authorize('update', $creditNote);
        $creditNote = $action->execute($creditNote, $request->validated());
        return response()->json(new CreditNoteResource($creditNote->load('items', 'customer', 'invoice')), Response::HTTP_OK);
    }

    public function validateCreditNote(Request $request, CreditNote $creditNote, ValidateCreditNoteAction $action): JsonResponse
    {
        $this->authorize('update', $creditNote);
        
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        try {
            $cn = $action->execute($creditNote, $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null);
            return response()->json([
                'message' => 'Credit note validated.',
                'credit_note' => new CreditNoteResource($cn->load('items', 'customer', 'invoice')),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        $this->authorize('delete', $creditNote);
        $creditNote->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
