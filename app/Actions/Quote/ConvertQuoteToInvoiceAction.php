<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Actions\Invoice\CreateInvoiceAction;
use App\DTOs\InvoiceDTO;
use App\Enums\QuoteStatus;
use App\Events\QuoteAccepted;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToInvoiceAction
{
    public function execute(Quote $quote, CreateInvoiceAction $createInvoiceAction): Invoice
    {
        return DB::transaction(function () use ($quote, $createInvoiceAction) {
            $items = $quote->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price_xof' => $item->unit_price_xof,
                    'tax_rate' => $item->tax_rate,
                ];
            })->toArray();

            $invoiceDTO = new InvoiceDTO(
                customer_id: $quote->customer_id,
                due_date: $quote->expiration_date->toDateString(),
                issue_date: $quote->issue_date->toDateString(),
                discount_xof: $quote->discount_xof,
                discount_type: $quote->discount_type,
                notes: $quote->notes,
                terms: $quote->terms,
                metadata: $quote->metadata,
                items: $items,
            );

            $invoice = $createInvoiceAction->execute($invoiceDTO);

            $quote->update([
                'status' => QuoteStatus::Accepted,
            ]);

            event(new QuoteAccepted($quote->fresh()));

            return $invoice->fresh();
        });
    }
}
