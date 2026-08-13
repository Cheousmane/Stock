<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Product;
use App\Notifications\LowStockDigestNotification;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

final class SendLowStockAlerts extends Command
{
    protected $signature = 'stock:alerts';

    protected $description = 'Envoie un digest quotidien des produits sous le seuil minimum (au plus une fois tous les 7 jours)';

    public function handle(): int
    {
        $companies = Company::where('status', '!=', 'suspended')->get();

        foreach ($companies as $company) {
            TenantContext::set($company);

            try {
                $this->alertCompany($company);
            } catch (\Throwable $e) {
                $this->error("[{$company->id}] {$e->getMessage()}");
            } finally {
                TenantContext::clear();
            }
        }

        return self::SUCCESS;
    }

    private function alertCompany(Company $company): void
    {
        $cutoff = now()->subDays(7);

        $products = Product::where('company_id', $company->id)
            ->where('min_stock', '>', 0)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->where(fn ($q) => $q->whereNull('last_stock_alert_at')->orWhere('last_stock_alert_at', '<', $cutoff))
            ->where('is_active', true)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $users = $company->users()->where('is_active', true)->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new LowStockDigestNotification($company, $products, $products->count()));
            $this->info("[{$company->id}] Digest stock bas envoyé à " . $users->count() . " utilisateur(s)");
        }

        Product::whereIn('id', $products->pluck('id'))->update(['last_stock_alert_at' => now()]);
    }
}