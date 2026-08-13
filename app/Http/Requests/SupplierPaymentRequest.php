<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class SupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'method' => ['required', 'string', 'in:cash,bank,stripe,mobile_money'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount_xof' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $amount = (int) $this->input('amount_xof');
                $supplierId = (int) $this->input('supplier_id');
                $poId = $this->input('purchase_order_id');

                if ($poId) {
                    $po = PurchaseOrder::find($poId);
                    if (!$po || (int) $po->supplier_id !== $supplierId) {
                        $validator->errors()->add(
                            'purchase_order_id',
                            trans('validation.supplier_payment_order_mismatch')
                        );
                        return;
                    }

                    $balanceDue = (int) $po->total_xof - (int) $po->paid_xof;
                    if ($amount > $balanceDue) {
                        $validator->errors()->add(
                            'amount_xof',
                            trans('validation.supplier_payment_exceeds_order', ['max' => max(0, $balanceDue)])
                        );
                    }
                    return;
                }

                $supplier = Supplier::find($supplierId);
                $balance = $supplier ? (int) $supplier->balance_xof : 0;
                if ($amount > $balance) {
                    $validator->errors()->add(
                        'amount_xof',
                        trans('validation.supplier_payment_exceeds_balance', ['max' => max(0, $balance)])
                    );
                }
            },
        ];
    }
}