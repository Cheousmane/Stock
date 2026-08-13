<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);
        $expenses = Expense::orderByDesc('date')
            ->when($category = $request->input('category'), function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->paginate($request->integer('per_page', 20));

        return ExpenseResource::collection($expenses)->response();
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        $this->authorize('create', Expense::class);
        $expense = Expense::create([
            'company_id' => TenantContext::getCompanyId(),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'amount' => $request->integer('amount'),
            'date' => $request->input('date'),
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(new ExpenseResource($expense), Response::HTTP_CREATED);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);
        $expense->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
