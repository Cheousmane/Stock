<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Quote\ConvertQuoteToInvoiceAction;
use App\Actions\Quote\CreateQuoteAction;
use App\Actions\Quote\UpdateQuoteAction;
use App\Actions\Quote\UpdateQuoteStatusAction;
use App\DTOs\QuoteDTO;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quote::class);

        $perPage = (int) $request->input('per_page', 15);
        $query = Quote::with(['customer', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json(QuoteResource::collection($query->paginate($perPage)), Response::HTTP_OK);
    }

    public function store(QuoteRequest $request, CreateQuoteAction $action): JsonResponse
    {
        $this->authorize('create', Quote::class);
        $dto = QuoteDTO::fromArray($request->validated());
        $quote = $action->execute($dto);
        $quote->load(['customer', 'items']);
        return response()->json(new QuoteResource($quote), Response::HTTP_CREATED);
    }

    public function show(Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);
        $quote->load(['customer', 'items']);
        return response()->json(new QuoteResource($quote), Response::HTTP_OK);
    }

    public function update(QuoteRequest $request, Quote $quote, UpdateQuoteAction $action): JsonResponse
    {
        $this->authorize('update', $quote);

        $quote = $action->execute($quote, $request->validated());
        $quote->load(['customer', 'items']);

        return response()->json(new QuoteResource($quote), Response::HTTP_OK);
    }

    public function destroy(Quote $quote): JsonResponse
    {
        $this->authorize('delete', $quote);
        $quote->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function markAsSent(Quote $quote, UpdateQuoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $quote);
        $quote = $action->execute($quote, QuoteStatus::Sent);
        $quote->load(['customer', 'items']);
        return response()->json(new QuoteResource($quote), Response::HTTP_OK);
    }

    public function markAsAccepted(Quote $quote, UpdateQuoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $quote);
        $quote = $action->execute($quote, QuoteStatus::Accepted);
        $quote->load(['customer', 'items']);
        return response()->json(new QuoteResource($quote), Response::HTTP_OK);
    }

    public function markAsRejected(Quote $quote, UpdateQuoteStatusAction $action): JsonResponse
    {
        $this->authorize('update', $quote);
        $quote = $action->execute($quote, QuoteStatus::Rejected);
        $quote->load(['customer', 'items']);
        return response()->json(new QuoteResource($quote), Response::HTTP_OK);
    }

    public function convertToInvoice(Quote $quote, ConvertQuoteToInvoiceAction $action, CreateInvoiceAction $createInvoiceAction): JsonResponse
    {
        $this->authorize('update', $quote);
        $this->authorize('create', \App\Models\Invoice::class);
        $invoice = $action->execute($quote, $createInvoiceAction);
        $invoice->load(['customer', 'items']);
        return response()->json(new InvoiceResource($invoice), Response::HTTP_CREATED);
    }
}
