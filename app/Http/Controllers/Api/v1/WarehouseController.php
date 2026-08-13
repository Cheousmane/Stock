<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Warehouse\CreateWarehouseAction;
use App\Actions\Warehouse\UpdateWarehouseAction;
use App\DTOs\WarehouseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);
        $warehouses = Warehouse::query()
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($isActive = $request->input('is_active'), function ($query) use ($isActive) {
                $query->where('is_active', $isActive === '1');
            })
            ->paginate($request->integer('per_page', 15));

        return WarehouseResource::collection($warehouses)->response();
    }

    public function store(WarehouseRequest $request, CreateWarehouseAction $action): JsonResponse
    {
        $this->authorize('create', Warehouse::class);
        $dto = WarehouseDTO::fromArray($request->validated());
        $warehouse = $action->execute($dto);
        return response()->json(new WarehouseResource($warehouse), Response::HTTP_CREATED);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);
        return response()->json(new WarehouseResource($warehouse), Response::HTTP_OK);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse, UpdateWarehouseAction $action): JsonResponse
    {
        $this->authorize('update', $warehouse);
        $dto = WarehouseDTO::fromArray($request->validated());
        $warehouse = $action->execute($warehouse, $dto);
        return response()->json(new WarehouseResource($warehouse), Response::HTTP_OK);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);
        $warehouse->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
