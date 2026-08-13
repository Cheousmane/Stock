<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DeliveryNote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DeliveryNotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return DeliveryNote::query()
            ->with(['customer:id,name', 'invoice:id,number'])
            ->withCount('items');
    }

    public function headings(): array
    {
        return [
            'Number',
            'Customer',
            'Status',
            'Issue Date',
            'Delivery Date',
            'Linked Invoice',
            'Items Count',
            'Notes',
            'Created At',
        ];
    }

    public function map($note): array
    {
        return [
            $note->number,
            $note->customer?->name,
            $note->status?->value,
            $note->issue_date?->format('Y-m-d'),
            $note->delivery_date?->format('Y-m-d'),
            $note->invoice_id ? $note->invoice?->number : null,
            $note->items_count ?? null,
            $note->notes,
            $note->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}