<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Customer;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Customer::query();
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
            'Balance (XOF)',
            'Credit Limit',
            'Status',
            'Created At',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->code,
            $customer->name,
            $customer->email,
            $customer->phone,
            $customer->city,
            $customer->country,
            $customer->tax_number,
            Money::format($customer->balance_xof ?? 0),
            Money::format($customer->credit_limit_xof ?? 0),
            $customer->is_active ? 'Active' : 'Inactive',
            $customer->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
