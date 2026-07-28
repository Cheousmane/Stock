<?php

declare(strict_types=1);

namespace App\Actions\Warehouse;

use App\DTOs\WarehouseDTO;
use App\Models\Warehouse;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateWarehouseAction
{
    public function execute(WarehouseDTO $dto): Warehouse
    {
        return DB::transaction(function () use ($dto) {
            $warehouse = Warehouse::create([
                'company_id' => TenantContext::getCompanyId(),
                'name' => $dto->name,
                'code' => $dto->code,
                'location' => $dto->location,
                'address' => $dto->address,
                'city' => $dto->city,
                'phone' => $dto->phone,
                'email' => $dto->email,
                'is_active' => $dto->is_active,
            ]);

            return $warehouse;
        });
    }
}
