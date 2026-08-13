<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PosSale;
use App\Support\DashboardCache;

class PosSaleObserver
{
    public function created(PosSale $posSale): void
    {
        DashboardCache::forget($posSale->company_id);

        if ($posSale->status !== 'completed') {
            return;
        }

        $this->createPayment($posSale);
    }

    public function updated(PosSale $posSale): void
    {
        if ($posSale->status !== 'completed') {
            return;
        }

        if ($posSale->payment()->exists()) {
            return;
        }

        $this->createPayment($posSale);
    }

    private function createPayment(PosSale $posSale): void
    {
        Payment::create([
            'company_id' => $posSale->company_id,
            'pos_sale_id' => $posSale->id,
            'method' => $posSale->payment_method,
            'reference' => $posSale->receipt_number,
            'amount_xof' => $posSale->total_xof,
            'status' => 'completed',
            'payment_date' => $posSale->created_at?->toDateString() ?? today()->toDateString(),
            'created_by' => $posSale->user_id,
            'notes' => 'Paiement automatique - Vente POS ' . $posSale->receipt_number,
        ]);
    }
}
