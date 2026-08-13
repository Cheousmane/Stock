<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\PaymentReminderMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Support\DashboardCache;
use App\Support\TenantContext;
use App\Support\TenantMailConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Marque les factures en retard et envoie les relances de paiement (J+3, J+8, retard J+3)';

    public function handle(): int
    {
        $companies = Company::where('status', '!=', 'suspended')->get();

        foreach ($companies as $company) {
            TenantContext::set($company);

            try {
                $this->markOverdue($company);
                $this->sendReminders($company);
            } catch (\Throwable $e) {
                $this->error("[{$company->id}] {$e->getMessage()}");
            } finally {
                TenantContext::clear();
            }
        }

        return self::SUCCESS;
    }

    private function markOverdue(Company $company): void
    {
        $overdue = Invoice::where('company_id', $company->id)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Partial])
            ->where('balance_due_xof', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => InvoiceStatus::Overdue]);

        if ($overdue > 0) {
            DashboardCache::forget($company->id);
        }
    }

    private function sendReminders(Company $company): void
    {
        TenantMailConfig::apply($company);

        $base = Invoice::where('company_id', $company->id)
            ->where('balance_due_xof', '>', 0)
            ->whereHas('customer', fn ($q) => $q->whereNotNull('email'));

        // Niveau 1 — J+3 après émission, facture encore envoyée
        $level1 = (clone $base)
            ->where('status', InvoiceStatus::Sent)
            ->where('reminder_level', 0)
            ->whereDate('issue_date', '<=', now()->subDays(3)->toDateString())
            ->get();

        // Niveau 2 — J+8 après émission
        $level2 = (clone $base)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Partial])
            ->where('reminder_level', '<', 2)
            ->whereDate('issue_date', '<=', now()->subDays(8)->toDateString())
            ->get();

        // Niveau 3 — 3 jours de retard effectif
        $level3 = (clone $base)
            ->where('status', InvoiceStatus::Overdue)
            ->where('reminder_level', '<', 3)
            ->whereDate('due_date', '<=', now()->subDays(3)->toDateString())
            ->get();

        $this->send($level1, 1);
        $this->send($level2, 2);
        $this->send($level3, 3);
    }

    private function send(\Illuminate\Support\Collection $invoices, int $level): void
    {
        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;

            if ($customer === null || ! $customer->email) {
                $this->skip($invoice, "Pas d'email client");
                continue;
            }

            try {
                Mail::to($customer->email)->send(new PaymentReminderMail($invoice, $level));
                $invoice->forceFill(['reminder_level' => $level, 'last_reminder_at' => now()])->save();
                $this->info("Relance niveau {$level} envoyée pour la facture {$invoice->number}");
            } catch (\Throwable $e) {
                $this->warn("Échec envoi relance {$invoice->number}: {$e->getMessage()}");
            }
        }
    }

    private function skip(Invoice $invoice, string $reason): void
    {
        $this->line("Facture {$invoice->number} ignorée: {$reason}");
    }
}