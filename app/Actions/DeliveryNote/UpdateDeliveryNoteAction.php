<?php

declare(strict_types=1);

namespace App\Actions\DeliveryNote;

use App\Models\DeliveryNote;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class UpdateDeliveryNoteAction
{
    public function execute(DeliveryNote $deliveryNote, array $data): DeliveryNote
    {
        return DB::transaction(function () use ($deliveryNote, $data) {
            $deliveryNote->update($data);

            if (isset($data['items'])) {
                $deliveryNote->items()->delete();

                $companyId = TenantContext::getCompanyId();

                foreach ($data['items'] as $item) {
                    $deliveryNote->items()->create([
                        'company_id' => $companyId,
                        'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                        'description' => $item['description'] ?? '',
                        'quantity' => (int) ($item['quantity'] ?? 1),
                    ]);
                }
            }

            return $deliveryNote->fresh();
        });
    }
}
