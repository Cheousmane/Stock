<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Customer\CreateCustomerAction;
use App\Actions\Customer\UpdateCustomerAction;
use App\DTOs\CustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = (int) $request->input('per_page', 15);
        $query = Customer::query();

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

        return response()->json(CustomerResource::collection($query->paginate($perPage)), Response::HTTP_OK);
    }

    public function store(CustomerRequest $request, CreateCustomerAction $action): JsonResponse
    {
        $this->authorize('create', Customer::class);
        $dto = CustomerDTO::fromArray($request->validated());
        $customer = $action->execute($dto);
        return response()->json(new CustomerResource($customer), Response::HTTP_CREATED);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        return response()->json(new CustomerResource($customer), Response::HTTP_OK);
    }

    public function update(CustomerRequest $request, Customer $customer, UpdateCustomerAction $action): JsonResponse
    {
        $this->authorize('update', $customer);
        $dto = CustomerDTO::fromArray($request->validated());
        $customer = $action->execute($customer, $dto);
        return response()->json(new CustomerResource($customer), Response::HTTP_OK);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $customer->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
