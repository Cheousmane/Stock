<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()->with('category');
    }

    public function headings(): array
    {
        return [
            'UUID',
            'Name',
            'SKU',
            'Barcode',
            'Category',
            'Price (XOF)',
            'Purchase Price',
            'Wholesale Price',
            'Min Stock',
            'Description',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->uuid,
            $product->name,
            $product->sku,
            $product->barcode,
            $product->category?->name,
            Money::format($product->price),
            Money::format($product->purchase_price_xof ?? 0),
            Money::format($product->wholesale_price_xof ?? 0),
            $product->min_stock,
            $product->description,
            $product->is_active ? 'Active' : 'Inactive',
            $product->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
