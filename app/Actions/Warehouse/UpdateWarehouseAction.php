<?php

declare(strict_types=1);

namespace App\Actions\Warehouse;

use App\DTOs\WarehouseDTO;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class UpdateWarehouseAction
{
    public function execute(Warehouse $warehouse, WarehouseDTO $dto): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $dto) {
            $warehouse->update([
                'name' => $dto->name,
                'code' => $dto->code,
                'location' => $dto->location,
                'address' => $dto->address,
                'city' => $dto->city,
                'phone' => $dto->phone,
                'email' => $dto->email,
                'is_active' => $dto->is_active,
            ]);

            return $warehouse->fresh();
        });
    }
}
