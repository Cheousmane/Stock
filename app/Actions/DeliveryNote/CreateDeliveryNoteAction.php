<?php

declare(strict_types=1);

namespace App\Actions\DeliveryNote;

use App\DTOs\DeliveryNoteDTO;
use App\Enums\DeliveryNoteStatus;
use App\Models\DeliveryNote;
use App\Support\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateDeliveryNoteAction
{
    public function execute(DeliveryNoteDTO $dto): DeliveryNote
    {
        $companyId = TenantContext::getCompanyId();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return $this->tryCreate($dto, $companyId);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) throw $e;
            }
        }

        throw new \RuntimeException('Impossible de créer le BL après 5 tentatives.');
    }

    private function tryCreate(DeliveryNoteDTO $dto, int $companyId): DeliveryNote
    {
        return DB::transaction(function () use ($dto, $companyId) {
            $number = $this->generateDeliveryNoteNumber($companyId);

            $deliveryNote = DeliveryNote::create([
                'company_id' => $companyId,
                'number' => $number,
                'customer_id' => $dto->customer_id,
                'invoice_id' => $dto->invoice_id,
                'status' => DeliveryNoteStatus::Pending,
                'issue_date' => $dto->issue_date,
                'delivery_date' => $dto->delivery_date,
                'notes' => $dto->notes,
                'signature' => $dto->signature,
            ]);

            foreach ($dto->items as $item) {
                $deliveryNote->items()->create([
                    'company_id' => $companyId,
                    'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                    'description' => $item['description'] ?? '',
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ]);
            }

            return $deliveryNote->fresh();
        });
    }

    private function generateDeliveryNoteNumber(int $companyId): string
    {
        $prefix = 'BL-' . now()->format('Y-m') . '-';

        $lastSequence = DB::table('delivery_notes')
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max(DB::raw('CAST(SUBSTRING_INDEX(number, \'-\', -1) AS UNSIGNED)'));

        $next = ($lastSequence ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
