<?php

declare(strict_types=1);

namespace App\Actions\Purchases;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePurchaseOrderAction
{
    public function execute(array $data): PurchaseOrder
    {
        $companyId = TenantContext::getCompanyId();

        if (empty($data['number'])) {
            $data['number'] = $this->generateNumber($companyId);
        }

        return DB::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $itemsData = $data['items'];
            unset($data['items']);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($itemsData as &$item) {
                $item['subtotal_xof'] = $item['quantity'] * $item['unit_price_xof'];
                $item['tax_amount_xof'] = $item['tax_amount_xof'] ?? 0;
                $item['discount_amount_xof'] = $item['discount_amount_xof'] ?? 0;
                $item['total_xof'] = $item['subtotal_xof'] + $item['tax_amount_xof'] - $item['discount_amount_xof'];

                $subtotal += $item['subtotal_xof'];
                $taxTotal += $item['tax_amount_xof'];

                if (!isset($item['name'])) {
                    $product = Product::find($item['product_id']);
                    $item['name'] = $product ? $product->name : 'Unknown Product';
                }
            }

            $discount = $data['discount_xof'] ?? 0;
            if (isset($data['discount_type']) && $data['discount_type'] === 'percentage') {
                $discount = (int) round($subtotal * ($discount / 100));
            }

            $data['status'] = $data['status'] ?? 'draft';
            $data['subtotal_xof'] = $subtotal;
            $data['tax_xof'] = $taxTotal;
            $data['discount_xof'] = $discount;
            $data['total_xof'] = $subtotal + $taxTotal - $discount;

            $purchaseOrder = PurchaseOrder::create($data);

            foreach ($itemsData as $itemData) {
                $itemData['company_id'] = $companyId;
                $itemData['purchase_order_id'] = $purchaseOrder->id;
                PurchaseOrderItem::create($itemData);
            }

            return $purchaseOrder;
        });
    }

    private function generateNumber(int $companyId): string
    {
        $prefix = 'PO-' . now()->format('Y-m') . '-';

        $lastSequence = DB::table('purchase_orders')
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max(DB::raw('CAST(SUBSTRING_INDEX(number, \'-\', -1) AS UNSIGNED)'));

        $next = ($lastSequence ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
