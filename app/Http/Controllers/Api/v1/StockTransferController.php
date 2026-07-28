<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Stock\CreateStockTransferAction;
use App\DTOs\StockTransferDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StockTransferController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StockTransfer::class);
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'product'])
            ->latest()
            ->paginate(15);

        return StockTransferResource::collection($transfers);
    }

    public function store(StockTransferRequest $request, CreateStockTransferAction $action): StockTransferResource
    {
        $this->authorize('create', StockTransfer::class);
        $dto = StockTransferDTO::fromArray($request->validated());

        $transfer = $action->execute($dto);

        return new StockTransferResource($transfer->load(['fromWarehouse', 'toWarehouse', 'product']));
    }

    public function show(StockTransfer $stockTransfer): StockTransferResource
    {
        $this->authorize('view', $stockTransfer);
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'product']);

        return new StockTransferResource($stockTransfer);
    }
}
