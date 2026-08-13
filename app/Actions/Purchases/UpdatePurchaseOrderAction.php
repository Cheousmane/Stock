<?php

declare(strict_types=1);

namespace App\Actions\Purchases;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class UpdatePurchaseOrderAction
{
    public function execute(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $data) {
            $companyId = TenantContext::getCompanyId();

            unset($data['number']);

            $purchaseOrder->update($data);

            if (isset($data['items'])) {
                $purchaseOrder->items()->delete();

                $subtotal = 0;
                $taxTotal = 0;

                foreach ($data['items'] as $item) {
                    $item['subtotal_xof'] = $item['quantity'] * $item['unit_price_xof'];
                    $item['tax_amount_xof'] = $item['tax_amount_xof'] ?? 0;
                    $item['discount_amount_xof'] = $item['discount_amount_xof'] ?? 0;
                    $item['total_xof'] = $item['subtotal_xof'] + $item['tax_amount_xof'] - $item['discount_amount_xof'];

                    $subtotal += $item['subtotal_xof'];
                    $taxTotal += $item['tax_amount_xof'];

                    if (!isset($item['name']) && !empty($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $item['name'] = $product ? $product->name : 'Unknown Product';
                    }

                    $item['company_id'] = $companyId;
                    $item['purchase_order_id'] = $purchaseOrder->id;
                    PurchaseOrderItem::create($item);
                }

                $discount = $data['discount_xof'] ?? $purchaseOrder->discount_xof ?? 0;
                if (isset($data['discount_type']) && $data['discount_type'] === 'percentage') {
                    $discount = (int) round($subtotal * ($discount / 100));
                }

                $purchaseOrder->update([
                    'subtotal_xof' => $subtotal,
                    'tax_xof' => $taxTotal,
                    'discount_xof' => $discount,
                    'total_xof' => $subtotal + $taxTotal - $discount,
                ]);
            }

            return $purchaseOrder->fresh([
                'supplier',
                'warehouse',
                'items',
            ]);
        });
    }
}