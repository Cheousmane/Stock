<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Support\Money;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return PurchaseOrder::query()->with('supplier:id,name');
    }

    public function headings(): array
    {
        return [
            'Number',
            'Supplier',
            'Status',
            'Issue Date',
            'Expected Date',
            'Subtotal',
            'Tax',
            'Total',
            'Paid',
            'Created At',
        ];
    }

    public function map($order): array
    {
        return [
            $order->number,
            $order->supplier?->name,
            $order->status,
            $order->issue_date?->format('Y-m-d'),
            $order->expected_delivery_date?->format('Y-m-d'),
            Money::format($order->subtotal_xof),
            Money::format($order->tax_xof),
            Money::format($order->total_xof),
            Money::format($order->paid_xof),
            $order->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}