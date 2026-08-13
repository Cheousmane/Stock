<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);
        $units = Unit::query()
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->paginate($request->integer('per_page', 15));

        return UnitResource::collection($units)->response();
    }

    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);
        return response()->json(new UnitResource($unit), Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Unit::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);
        $unit = Unit::create($validated);
        return response()->json(new UnitResource($unit), Response::HTTP_CREATED);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
        ]);
        $unit->update($validated);
        return response()->json(new UnitResource($unit), Response::HTTP_OK);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);
        $unit->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
