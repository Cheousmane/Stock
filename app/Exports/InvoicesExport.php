<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Invoice;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Invoice::query()->with('customer');
    }

    public function headings(): array
    {
        return [
            'Number',
            'Customer',
            'Status',
            'Issue Date',
            'Due Date',
            'Subtotal',
            'Tax',
            'Total',
            'Paid',
            'Balance',
            'Created At',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->number,
            $invoice->customer?->name,
            $invoice->status?->value,
            $invoice->issue_date?->format('Y-m-d'),
            $invoice->due_date?->format('Y-m-d'),
            Money::format($invoice->subtotal_xof),
            Money::format($invoice->tax_xof),
            Money::format($invoice->total_xof),
            Money::format($invoice->paid_xof),
            Money::format($invoice->balance_due_xof),
            $invoice->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
