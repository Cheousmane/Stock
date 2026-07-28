<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\DTOs\CustomerDTO;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerAction
{
    public function execute(Customer $customer, CustomerDTO $dto): Customer
    {
        return DB::transaction(function () use ($customer, $dto) {
            $customer->update([
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

            return $customer->fresh();
        });
    }
}
