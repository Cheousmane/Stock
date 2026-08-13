<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\CreditNote;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CreditNotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return CreditNote::query()->with(['customer:id,name', 'invoice:id,number']);
    }

    public function headings(): array
    {
        return [
            'Number',
            'Customer',
            'Status',
            'Linked Invoice',
            'Issue Date',
            'Subtotal',
            'Tax',
            'Total',
            'Notes',
            'Created At',
        ];
    }

    public function map($creditNote): array
    {
        return [
            $creditNote->number,
            $creditNote->customer?->name,
            $creditNote->status,
            $creditNote->invoice?->number,
            $creditNote->issue_date?->format('Y-m-d'),
            Money::format($creditNote->subtotal_xof),
            Money::format($creditNote->tax_xof),
            Money::format($creditNote->total_xof),
            $creditNote->notes,
            $creditNote->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}