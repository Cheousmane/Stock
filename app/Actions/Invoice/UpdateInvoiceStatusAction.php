<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceStatusAction
{
    public function execute(Invoice $invoice, InvoiceStatus $status): Invoice
    {
        return DB::transaction(function () use ($invoice, $status) {
            $invoice->update([
                'status' => $status,
            ]);

            return $invoice->fresh();
        });
    }
}
