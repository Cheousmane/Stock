<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tax::class);
        $taxes = Tax::query()->paginate(15);
        return response()->json(TaxResource::collection($taxes), Response::HTTP_OK);
    }

    public function store(TaxRequest $request): JsonResponse
    {
        $this->authorize('create', Tax::class);
        $tax = Tax::create($request->validated());
        return response()->json(new TaxResource($tax), Response::HTTP_CREATED);
    }

    public function show(Tax $tax): JsonResponse
    {
        $this->authorize('view', $tax);
        return response()->json(new TaxResource($tax), Response::HTTP_OK);
    }

    public function update(TaxRequest $request, Tax $tax): JsonResponse
    {
        $this->authorize('update', $tax);
        $tax->update($request->validated());
        return response()->json(new TaxResource($tax->fresh()), Response::HTTP_OK);
    }

    public function destroy(Tax $tax): JsonResponse
    {
        $this->authorize('delete', $tax);
        $tax->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
