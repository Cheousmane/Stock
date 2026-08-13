<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Expense::query();
    }

    public function headings(): array
    {
        return [
            'Description',
            'Category',
            'Amount (XOF)',
            'Date',
            'Created At',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->description,
            $expense->category,
            $expense->amount,
            $expense->date?->format('Y-m-d'),
            $expense->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}