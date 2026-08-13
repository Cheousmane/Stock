<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Quote;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Quote::query()->with('customer:id,name');
    }

    public function headings(): array
    {
        return [
            'Number',
            'Customer',
            'Status',
            'Issue Date',
            'Expiration Date',
            'Subtotal',
            'Tax',
            'Total',
            'Notes',
            'Created At',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->number,
            $quote->customer?->name,
            $quote->status?->value,
            $quote->issue_date?->format('Y-m-d'),
            $quote->expiration_date?->format('Y-m-d'),
            Money::format($quote->subtotal_xof),
            Money::format($quote->tax_xof),
            Money::format($quote->total_xof),
            $quote->notes,
            $quote->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}