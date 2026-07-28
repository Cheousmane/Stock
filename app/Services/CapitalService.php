<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\PosSale;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class CapitalService
{
    public function calculate(Company $company): array
    {
        $revenue = $this->revenue($company->id);
        $outflows = $this->outflows($company->id);
        $initialCapital = $company->manual_capital ?? 0;
        $calculated = $initialCapital + $revenue - $outflows;

        return [
            'initial_capital' => (int) $initialCapital,
            'revenue' => (int) $revenue,
            'outflows' => (int) $outflows,
            'calculated' => max(0, (int) $calculated),
            'is_manual' => $company->manual_capital !== null,
            'capital_updated_at' => $company->capital_updated_at?->toIso8601String(),
        ];
    }

    private function revenue(int $companyId): int
    {
        $fromPayments = (int) DB::table('payments')
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNull('pos_sale_id')
            ->sum('amount_xof');

        $fromPosSales = (int) PosSale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->sum('total_xof');

        return $fromPayments + $fromPosSales;
    }

    private function outflows(int $companyId): int
    {
        $expenses = (int) Expense::where('company_id', $companyId)->sum('amount');

        $purchases = (int) PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'received')
            ->sum('total_xof');

        $creditNotes = (int) CreditNote::where('company_id', $companyId)->sum('total_xof');

        return $expenses + $purchases + $creditNotes;
    }
}
