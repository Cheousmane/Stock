<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\DTOs\PaymentDTO;
use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreatePaymentAction
{
    public function execute(PaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            $companyId = TenantContext::getCompanyId();

            $payment = Payment::create([
                'company_id' => $companyId,
                'invoice_id' => $dto->invoice_id,
                'method' => $dto->method ?? 'cash',
                'reference' => $dto->reference,
                'amount_xof' => $dto->amount_xof,
                'status' => $dto->status ?? 'completed',
                'payment_date' => $dto->payment_date,
                'notes' => $dto->notes,
                'metadata' => $dto->metadata,
                'created_by' => $dto->created_by,
            ]);

            $invoice = Invoice::findOrFail($dto->invoice_id);

            $newPaid = Money::add($invoice->paid_xof, $dto->amount_xof);
            $newBalance = Money::subtract($invoice->total_xof, $newPaid);

            if ($newBalance < 0) {
                $newBalance = 0;
                $newPaid = $invoice->total_xof;
            }

            $invoice->update([
                'paid_xof' => $newPaid,
                'balance_due_xof' => $newBalance,
                'status' => $newBalance === 0 ? InvoiceStatus::Paid : $invoice->status,
            ]);

            $customer = $invoice->customer;
            if ($customer) {
                $newCustomerBalance = Money::subtract($customer->balance_xof, $dto->amount_xof);
                if ($newCustomerBalance < 0) {
                    $newCustomerBalance = 0;
                }
                $customer->update([
                    'balance_xof' => $newCustomerBalance,
                ]);
            }

            event(new InvoicePaid($invoice, $payment));
            event(new PaymentReceived($payment));

            return $payment;
        });
    }
}
