<?php

use App\Models\Expense;
use App\Models\SupplierPayment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Expense::where('category', 'supplier_payment')
            ->whereNull('metadata')
            ->get()
            ->each(function (Expense $expense) {
                $payment = SupplierPayment::with(['supplier:id,name', 'purchaseOrder:id,number'])
                    ->where('amount_xof', $expense->amount)
                    ->whereDate('payment_date', $expense->date)
                    ->get()
                    ->first(fn (SupplierPayment $p) => $p->supplier && str_contains($expense->description, (string) $p->supplier->name));

                if (! $payment) {
                    return;
                }

                $expense->update(['metadata' => [
                    'supplier_id' => $payment->supplier_id,
                    'supplier_name' => $payment->supplier?->name,
                    'purchase_order_id' => $payment->purchase_order_id,
                    'purchase_order_number' => $payment->purchaseOrder?->number,
                    'payment_method' => $payment->method,
                ]]);
            });
    }

    public function down(): void
    {
        Expense::where('category', 'supplier_payment')
            ->whereNotNull('metadata')
            ->update(['metadata' => null]);
    }
};