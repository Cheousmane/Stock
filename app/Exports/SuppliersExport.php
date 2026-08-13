<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Supplier;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuppliersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Supplier::query();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Email',
            'Phone',
            'City',
            'Country',
            'Tax Number',
            'Registration Number',
            'Balance',
            'Active',
            'Created At',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->code,
            $supplier->name,
            $supplier->email,
            $supplier->phone,
            $supplier->city,
            $supplier->country,
            $supplier->tax_number,
            $supplier->registration_number,
            Money::format($supplier->balance_xof),
            $supplier->is_active ? 'Yes' : 'No',
            $supplier->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}