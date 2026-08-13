<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $perPage = $request->integer('per_page', 15);
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        if ($isActive = $request->input('is_active')) {
            $query->where('is_active', $isActive === '1');
        }

        return response()->json($query->latest()->paginate($perPage), Response::HTTP_OK);
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);
        $supplier = Supplier::create($request->validated());
        return response()->json($supplier, Response::HTTP_CREATED);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);
        return response()->json($supplier);
    }

    public function update(SupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);
        $supplier->update($request->validated());
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);
        $supplier->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', Supplier::class);
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:suppliers,id',
        ]);
        $count = Supplier::whereIn('id', $validated['ids'])->delete();

        return response()->json(['deleted' => $count], Response::HTTP_OK);
    }
}
