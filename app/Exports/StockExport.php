<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\WarehouseStock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return WarehouseStock::query()
            ->with(['product', 'warehouse']);
    }

    public function headings(): array
    {
        return [
            'Product',
            'SKU',
            'Warehouse',
            'Quantity',
            'Reserved',
            'Available',
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->product?->name,
            $stock->product?->sku,
            $stock->warehouse?->name,
            $stock->quantity,
            $stock->reserved_quantity,
            $stock->available_quantity,
        ];
    }
}
