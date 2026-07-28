<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\DTOs\CustomerDTO;
use App\Events\CustomerCreated;
use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateCustomerAction
{
    public function execute(CustomerDTO $dto): Customer
    {
        return DB::transaction(function () use ($dto) {
            $customer = Customer::create([
                'company_id' => TenantContext::getCompanyId(),
                'code' => $dto->code,
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'address' => $dto->address,
                'city' => $dto->city,
                'country' => $dto->country,
                'tax_number' => $dto->tax_number,
                'registration_number' => $dto->registration_number,
                'credit_limit_xof' => $dto->credit_limit_xof,
                'balance_xof' => $dto->balance_xof,
                'notes' => $dto->notes,
                'is_active' => $dto->is_active,
            ]);

            event(new CustomerCreated($customer));

            return $customer;
        });
    }
}
