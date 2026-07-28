<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PosSale;
use Illuminate\Console\Command;

class BackfillPosSalePayments extends Command
{
    protected $signature = 'app:backfill-pos-sale-payments';

    protected $description = "Crée un Payment pour chaque vente POS existante qui n'en a pas encore";

    public function handle(): void
    {
        $sales = PosSale::where('status', 'completed')
            ->whereDoesntHave('payment')
            ->get();

        if ($sales->isEmpty()) {
            $this->info('Aucune vente POS à backfill.');
            return;
        }

        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        foreach ($sales as $sale) {
            Payment::create([
                'company_id' => $sale->company_id,
                'pos_sale_id' => $sale->id,
                'method' => $sale->payment_method,
                'reference' => $sale->receipt_number,
                'amount_xof' => $sale->total_xof,
                'status' => 'completed',
                'payment_date' => $sale->created_at?->toDateString() ?? today()->toDateString(),
                'created_by' => $sale->user_id,
                'notes' => 'Backfill - Vente POS ' . $sale->receipt_number,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$sales->count()} payment(s) créé(s) avec succès.");
    }
}
