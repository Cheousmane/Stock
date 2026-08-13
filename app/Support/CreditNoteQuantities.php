<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CreditNoteItem;
use App\Models\InvoiceItem;

class CreditNoteQuantities
{
    public static function available(
        int $companyId,
        int $customerId,
        ?int $invoiceId = null,
        ?int $excludeCreditNoteId = null
    ): array {
        $soldPerProduct = InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($companyId, $customerId, $invoiceId) {
                $q->where('company_id', $companyId)
                    ->where('customer_id', $customerId);

                if ($invoiceId !== null) {
                    $q->where('id', $invoiceId);
                }
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as sold_qty')
            ->pluck('sold_qty', 'product_id');

        $creditedPerProduct = CreditNoteItem::query()
            ->whereHas('creditNote', function ($q) use ($companyId, $customerId, $invoiceId, $excludeCreditNoteId) {
                $q->where('company_id', $companyId)
                    ->where('customer_id', $customerId);

                if ($invoiceId !== null) {
                    $q->where('invoice_id', $invoiceId);
                }

                if ($excludeCreditNoteId !== null) {
                    $q->where('id', '!=', $excludeCreditNoteId);
                }
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as credited_qty')
            ->pluck('credited_qty', 'product_id');

        $available = [];

        foreach ($soldPerProduct as $productId => $soldQty) {
            $creditedQty = (int) ($creditedPerProduct[$productId] ?? 0);
            $available[(int) $productId] = max(0, (int) $soldQty - $creditedQty);
        }

        return $available;
    }
}