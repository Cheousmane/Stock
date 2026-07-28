<?php

declare(strict_types=1);

namespace App\Actions\DeliveryNote;

use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;

class UpdateDeliveryNoteStatusAction
{
    public function execute(DeliveryNote $deliveryNote, DeliveryNoteStatus $status): DeliveryNote
    {
        return DB::transaction(function () use ($deliveryNote, $status) {
            $data = ['status' => $status];

            if ($status === DeliveryNoteStatus::Shipped) {
                $data['delivery_date'] = now();
            }

            $deliveryNote->update($data);

            return $deliveryNote->fresh();
        });
    }
}
