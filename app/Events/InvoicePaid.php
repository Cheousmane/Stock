<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;

final class InvoicePaid
{
    use Dispatchable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Payment $payment,
    ) {}
}
