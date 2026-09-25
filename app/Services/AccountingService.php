<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\AccountingJournal;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PosSale;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class AccountingService
{
    public function generateSalesJournal(Company $company, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start = $start ?? Carbon::now()->startOfMonth();
        $end = $end ?? Carbon::now()->endOfMonth();

        $invoices = Invoice::where('company_id', $company->id)
            ->whereBetween('issue_date', [$start, $end])
            ->where('status', 'paid')
            ->get();

        $entries = [];

        foreach ($invoices as $invoice) {
            // Debit: Accounts Receivable
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'accounts_receivable'),
                'amount' => $invoice->total_xof,
                'description' => "Facture #{$invoice->number} - {$invoice->customer?->name ?? 'Client'}",
                'entry_date' => $invoice->issue_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
            ];

            // Credit: Revenue
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'revenue_sales'),
                'amount' => $invoice->total_xof,
                'description' => "Revenus facture #{$invoice->number}",
                'entry_date' => $invoice->issue_date,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
            ];
        }

        return Collection::make($entries);
    }

    public function generatePurchaseJournal(Company $company, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start = $start ?? Carbon::now()->startOfMonth();
        $end = $end ?? Carbon::now()->endOfMonth();

        $purchaseOrders = PurchaseOrder::where('company_id', $company->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'received')
            ->get();

        $entries = [];

        foreach ($purchaseOrders as $po) {
            // Debit: Purchases
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'purchases'),
                'amount' => $po->total_xof,
                'description' => "Bon d'achat #{$po->number} - {$po->supplier?->name ?? 'Fournisseur'}",
                'entry_date' => $po->created_at,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
            ];

            // Credit: Accounts Payable
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'accounts_payable'),
                'amount' => $po->total_xof,
                'description' => "Dette fournisseur #{$po->number}",
                'entry_date' => $po->created_at,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
            ];
        }

        return Collection::make($entries);
    }

    public function generateCashJournal(Company $company, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start = $start ?? Carbon::now()->startOfMonth();
        $end = $end ?? Carbon::now()->endOfMonth();

        $payments = Payment::where('company_id', $company->id)
            ->whereBetween('payment_date', [$start, $end])
            ->where('method', 'cash')
            ->get();

        $entries = [];

        foreach ($payments as $payment) {
            // Debit: Cash
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'cash'),
                'amount' => $payment->amount_xof,
                'description' => "Paiement cash #{$payment->reference}",
                'entry_date' => $payment->payment_date,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
            ];

            // Credit: Customer or Revenue (simplified)
            $entries[] = [
                'account_code' => $this->getOrCreateAccountCode($company, 'revenue_sales'),
                'amount' => $payment->amount_xof,
                'description' => "Recouvrement cash - {$payment->invoice?->number ?? 'inconnu'}",
                'entry_date' => $payment->payment_date,
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
            ];
        }

        // Add opening balance
        $entries[] = [
            'account_code' => $this->getOrCreateAccountCode($company, 'cash'),
            'amount' => 0, // Opening balance would be added separately
            'description' => 'Solde initial de caisse',
            'entry_date' => $start,
        ];

        return Collection::make($entries);
    }

    public function generateBalanceSheet(Company $company, ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now();

        // Assets
        $assets = $this->getTotalByAccountType($company, 'asset', $date);
        $totalAssets = $assets->sum('amount_xof');

        // Liabilities
        $liabilities = $this->getTotalByAccountType($company, 'liability', $date);
        $totalLiabilities = $liabilities->sum('amount_xof');

        // Equity
        $equity = $this->getEquity($company, $date);

        return Collection::make([
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'balance_equation' => "ACTIFS ({$totalAssets} XOF) = LIABILITÉS ({$totalLiabilities} XOF) + CAPITAL ({$equity} XOF)",
            'generated_at' => now(),
        ]);
    }

    public function generateIncomeStatement(Company $company, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start = $start ?? Carbon::now()->startOfMonth();
        $end = $end ?? Carbon::now()->endOfMonth();

        $revenueAccount = $this->getOrCreateAccountCode($company, 'revenue_sales');
        $expenseAccounts = $this->getExpenseAccounts($company);

        $totalRevenue = AccountingEntry::where('company_id', $company->id)
            ->where('entry_type', 'credit')
            ->where('account_code', 'like', "{$revenueAccount}%")
            ->whereBetween('entry_date', [$start, $end])
            ->sum('amount_xof');

        $totalExpenses = AccountingEntry::where('company_id', $company->id)
            ->where('entry_type', 'debit')
            ->whereIn('account_code', $expenseAccounts->pluck('code')->toArray())
            ->whereBetween('entry_date', [$start, $end])
            ->sum('amount_xof');

        $profit = $totalRevenue - $totalExpenses;

        return Collection::make([
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $profit,
            'profit_margin' => $totalRevenue > 0 ? round(($profit / $totalRevenue) * 100, 2) : 0,
            'period_start' => $start->format('d/m/Y'),
            'period_end' => $end->format('d/m/Y'),
            'generated_at' => now(),
        ]);
    }

    private function getTotalByAccountType(Company $company, string $type, ?Carbon $date): Collection
    {
        $account = AccountingAccount::where('company_id', $company->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return Collection::make();
        }

        $entries = AccountingEntry::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->where('entry_date', '<=', $date)
            ->get();

        $debits = $entries->where('entry_type', 'debit')->sum('amount_xof');
        $credits = $entries->where('entry_type', 'credit')->sum('amount_xof');

        return Collection::make([
            'account_code' => $account->code,
            'account_name' => $account->name,
            'debits' => $debits,
            'credits' => $credits,
            'net' => $debits - $credits,
        ]);
    }

    private function getEquity(Company $company, ?Carbon $date): int
    {
        // Simplified: capital + retained earnings
        $capitalAccount = AccountingAccount::where('company_id', $company->id)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->first();

        if (!$capitalAccount) {
            return 0;
        }

        $entries = AccountingEntry::where('company_id', $company->id)
            ->where('account_id', $capitalAccount->id)
            ->where('entry_date', '<=', $date)
            ->get();

        $debits = $entries->where('entry_type', 'debit')->sum('amount_xof');
        $credits = $entries->where('entry_type', 'credit')->sum('amount_xof');

        return $credits - $debits; // Equity normally has credit balance
    }

    private function getExpenseAccounts(Company $company): Collection
    {
        return AccountingAccount::where('company_id', $company->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();
    }

    private function getOrCreateAccountCode(Company $company, string $accountType): string
    {
        $account = AccountingAccount::where('company_id', $company->id)
            ->where('type', $accountType)
            ->where('is_active', true)
            ->first();

        return $account ? $account->code : 'GEN-' . strtoupper($accountType);
    }
}