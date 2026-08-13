<?php

declare(strict_types=1);

namespace App\Actions\Purchases;

use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateSupplierPaymentAction
{
    public function execute(array $data): SupplierPayment
    {
        $companyId = TenantContext::getCompanyId();

        return DB::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $data['created_by'] = auth()->id();

            $payment = SupplierPayment::create($data);

            $supplier = Supplier::find($data['supplier_id']);
            if ($supplier) {
                $supplier->decrement('balance_xof', $data['amount_xof']);
            }

            $po = null;
            if (!empty($data['purchase_order_id'])) {
                $po = PurchaseOrder::find($data['purchase_order_id']);
                if ($po) {
                    $po->increment('paid_xof', $data['amount_xof']);
                }
            }

            $description = 'Paiement fournisseur : ' . ($supplier?->name ?? '');
            if ($po) {
                $description .= ' — ' . $po->number;
            }

            Expense::create([
                'company_id' => $companyId,
                'description' => $description,
                'category' => 'supplier_payment',
                'amount' => $data['amount_xof'],
                'date' => $data['payment_date'] ?? now(),
                'created_by' => $data['created_by'],
                'metadata' => array_filter([
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'supplier_name' => $supplier?->name,
                    'purchase_order_id' => $po?->id,
                    'purchase_order_number' => $po?->number,
                    'payment_method' => $data['payment_method'] ?? null,
                ]),
            ]);

            return $payment;
        });
    }
}