<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateCreditNoteAction
{
    public function execute(array $data): CreditNote
    {
        $companyId = TenantContext::getCompanyId();

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
            $data['subtotal_xof'] = $subtotal;
            $data['tax_xof'] = $taxTotal;
            $data['total_xof'] = $subtotal + $taxTotal - $discount;

            $creditNote = CreditNote::create($data);

            foreach ($itemsData as $itemData) {
                $itemData['company_id'] = $companyId;
                $itemData['credit_note_id'] = $creditNote->id;
                CreditNoteItem::create($itemData);
            }

            return $creditNote;
        });
    }
}
