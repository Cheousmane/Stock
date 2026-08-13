<?php

declare(strict_types=1);

namespace App\Actions\DeliveryNote;

use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;

class UpdateDeliveryNoteStatusAction
{
    private const TRANSITIONS = [
        DeliveryNoteStatus::Pending->value => [DeliveryNoteStatus::Shipped, DeliveryNoteStatus::Returned],
        DeliveryNoteStatus::Shipped->value => [DeliveryNoteStatus::Delivered, DeliveryNoteStatus::Returned],
        DeliveryNoteStatus::Delivered->value => [DeliveryNoteStatus::Returned],
        DeliveryNoteStatus::Returned->value => [],
    ];

    public function execute(DeliveryNote $deliveryNote, DeliveryNoteStatus $status): DeliveryNote
    {
        $current = $deliveryNote->status;

        if ($current === $status) {
            return $deliveryNote->fresh();
        }

        $allowed = self::TRANSITIONS[(string) $current] ?? [];
        if (!in_array($status, $allowed, true)) {
            abort(422, sprintf(
                'Transition de statut invalide : %s -> %s',
                (string) $current,
                (string) $status
            ));
        }

        return DB::transaction(function () use ($deliveryNote, $status) {
            $data = ['status' => $status];

            if ($status === DeliveryNoteStatus::Shipped && $deliveryNote->delivery_date === null) {
                $data['delivery_date'] = now();
            }

            $deliveryNote->update($data);

            return $deliveryNote->fresh();
        });
    }
}