<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\CreditNote;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class UpdateCreditNoteAction
{
    public function execute(CreditNote $creditNote, array $data): CreditNote
    {
        $companyId = TenantContext::getCompanyId();

        return DB::transaction(function () use ($creditNote, $data, $companyId) {
            $itemsData = $data['items'] ?? null;
            unset($data['items']);

            $creditNote->update($data);

            if ($itemsData !== null) {
                $creditNote->items()->delete();

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
                        $product = $item['product_id'] ? Product::find($item['product_id']) : null;
                        $item['name'] = $product ? $product->name : ($item['description'] ?? 'Unknown Product');
                    }
                }

                $discount = $data['discount_xof'] ?? $creditNote->discount_xof ?? 0;
                $total = $subtotal + $taxTotal - $discount;

                $creditNote->update([
                    'subtotal_xof' => $subtotal,
                    'tax_xof' => $taxTotal,
                    'total_xof' => $total,
                ]);

                foreach ($itemsData as $itemData) {
                    $itemData['company_id'] = $companyId;
                    $itemData['credit_note_id'] = $creditNote->id;
                    $creditNote->items()->create($itemData);
                }
            }

            return $creditNote->fresh();
        });
    }
}
