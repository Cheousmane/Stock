<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private const CACHE_TTL = 300;
    private const CACHE_PREFIX = 'analytics';

    public function overview(): array
    {
        $companyId = TenantContext::getCompanyId();

        return Cache::remember(self::CACHE_PREFIX . ".{$companyId}.overview", self::CACHE_TTL, function () use ($companyId) {
            $totalRevenue = Invoice::where('company_id', $companyId)
                ->whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::Sent, InvoiceStatus::Partial])
                ->sum('total_xof');

            $paidRevenue = Invoice::where('company_id', $companyId)
                ->where('status', InvoiceStatus::Paid)
                ->sum('total_xof');

            $outstanding = Invoice::where('company_id', $companyId)
                ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue, InvoiceStatus::Partial])
                ->sum('balance_due_xof');

            $totalExpenses = Expense::where('company_id', $companyId)->sum('amount');
            $monthlyExpenses = Expense::where('company_id', $companyId)
                ->where('date', '>=', now()->subDays(30))
                ->sum('amount');

            $totalCustomers = Customer::where('company_id', $companyId)->count();
            $totalProducts = Product::where('company_id', $companyId)->count();
            $totalInvoices = Invoice::where('company_id', $companyId)->count();
            $paidInvoicesCount = Invoice::where('company_id', $companyId)
                ->where('status', InvoiceStatus::Paid)
                ->count();

            $lowStockCount = DB::table('products as p')
                ->where('p.company_id', $companyId)
                ->whereRaw('(SELECT COALESCE(SUM(ws.quantity), 0) FROM warehouse_stock ws WHERE ws.product_id = p.id) <= p.min_stock')
                ->count();

            $profitData = app(ProfitService::class)->getSummary($companyId);

            $thisMonth = Invoice::where('company_id', $companyId)
                ->whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::Sent, InvoiceStatus::Partial])
                ->whereYear('issue_date', now()->year)
                ->whereMonth('issue_date', now()->month)
                ->sum('total_xof');

            $lastMonth = Invoice::where('company_id', $companyId)
                ->whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::Sent, InvoiceStatus::Partial])
                ->whereYear('issue_date', now()->subMonth()->year)
                ->whereMonth('issue_date', now()->subMonth()->month)
                ->sum('total_xof');

            $growthRate = $lastMonth > 0
                ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 2)
                : 0;

            return [
                'revenue' => [
                    'total_xof' => $totalRevenue,
                    'paid_xof' => $paidRevenue,
                    'outstanding_xof' => $outstanding,
                    'this_month_xof' => $thisMonth,
                    'last_month_xof' => $lastMonth,
                    'growth_rate' => $growthRate,
                ],
                'profits' => [
                    'total_cost_xof' => $profitData['total_cost_xof'],
                    'total_margin_xof' => $profitData['total_margin_xof'],
                    'average_margin_rate' => $profitData['average_margin_rate'],
                ],
                'expenses' => [
                    'total_xof' => $totalExpenses,
                    'last_30_days_xof' => $monthlyExpenses,
                ],
                'counts' => [
                    'customers' => $totalCustomers,
                    'products' => $totalProducts,
                    'invoices' => $totalInvoices,
                    'paid_invoices' => $paidInvoicesCount,
                    'low_stock_products' => $lowStockCount,
                ],
            ];
        });
    }

    public function revenueGrowth(?int $months = 12): Collection
    {
        $companyId = TenantContext::getCompanyId();
        $cacheKey = self::CACHE_PREFIX . ".{$companyId}.revenue_growth.{$months}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $months) {
            $startDate = now()->subMonths($months - 1)->startOfMonth();

            $grouped = Invoice::where('company_id', $companyId)
                ->whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::Sent, InvoiceStatus::Partial])
                ->where('issue_date', '>=', $startDate)
                ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') as month, SUM(total_xof) as total_xof, COUNT(*) as invoice_count")
                ->groupBy(DB::raw("DATE_FORMAT(issue_date, '%Y-%m')"))
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $result = collect();
            $previousRevenue = null;

            for ($i = $months - 1; $i >= 0; $i--) {
                $month = now()->subMonths($i)->format('Y-m');
                $data = $grouped->get($month);
                $revenue = $data ? (int) $data->total_xof : 0;
                $count = $data ? (int) $data->invoice_count : 0;

                $growth = $previousRevenue !== null && $previousRevenue > 0
                    ? round(($revenue - $previousRevenue) / $previousRevenue * 100, 2)
                    : ($previousRevenue === null ? null : -100);

                $result->push([
                    'month' => $month,
                    'revenue_xof' => $revenue,
                    'invoice_count' => $count,
                    'growth_rate' => $growth,
                ]);

                $previousRevenue = $revenue;
            }

            return $result;
        });
    }

    public function topProducts(?int $limit = 10): Collection
    {
        $companyId = TenantContext::getCompanyId();
        $cacheKey = self::CACHE_PREFIX . ".{$companyId}.top_products.{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $limit) {
            $results = InvoiceItem::where('invoice_items.company_id', $companyId)
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereIn('invoices.status', [InvoiceStatus::Paid, InvoiceStatus::Sent])
                ->whereNotNull('invoice_items.product_id')
                ->groupBy('invoice_items.product_id')
                ->select(
                    'invoice_items.product_id',
                    DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity) as revenue'),
                    DB::raw('SUM(invoice_items.total_cost_xof) as cost'),
                    DB::raw('SUM(invoice_items.margin_xof) as margin'),
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('COUNT(DISTINCT invoices.id) as invoice_count'),
                )
                ->orderByDesc(DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity)'))
                ->limit($limit)
                ->get();

            $products = Product::whereIn('id', $results->pluck('product_id'))
                ->get(['id', 'name', 'sku', 'image'])
                ->keyBy('id');

            return $results->map(function ($row) use ($products) {
                $product = $products->get($row->product_id);
                $revenue = (int) $row->revenue;

                return [
                    'product_id' => $row->product_id,
                    'name' => $product?->name ?? 'Deleted',
                    'sku' => $product?->sku ?? '',
                    'total_quantity' => (int) $row->total_quantity,
                    'invoice_count' => (int) $row->invoice_count,
                    'revenue_xof' => $revenue,
                    'cost_xof' => (int) $row->cost,
                    'margin_xof' => (int) $row->margin,
                    'margin_rate' => $revenue > 0 ? round((int) $row->margin / $revenue * 100, 2) : 0,
                ];
            })->values();
        });
    }

    public function topCustomers(?int $limit = 10): Collection
    {
        $companyId = TenantContext::getCompanyId();
        $cacheKey = self::CACHE_PREFIX . ".{$companyId}.top_customers.{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $limit) {
            $results = InvoiceItem::where('invoice_items.company_id', $companyId)
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereIn('invoices.status', [InvoiceStatus::Paid, InvoiceStatus::Sent])
                ->groupBy('invoices.customer_id')
                ->select(
                    'invoices.customer_id',
                    DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity) as revenue'),
                    DB::raw('SUM(invoice_items.total_cost_xof) as cost'),
                    DB::raw('SUM(invoice_items.margin_xof) as margin'),
                    DB::raw('COUNT(DISTINCT invoices.id) as invoice_count'),
                )
                ->orderByDesc(DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity)'))
                ->limit($limit)
                ->get();

            $customers = Customer::whereIn('id', $results->pluck('customer_id'))
                ->get(['id', 'name', 'email'])
                ->keyBy('id');

            return $results->map(function ($row) use ($customers) {
                $customer = $customers->get($row->customer_id);
                $revenue = (int) $row->revenue;

                return [
                    'customer_id' => $row->customer_id,
                    'name' => $customer?->name ?? 'Deleted',
                    'email' => $customer?->email ?? '',
                    'invoice_count' => (int) $row->invoice_count,
                    'revenue_xof' => $revenue,
                    'cost_xof' => (int) $row->cost,
                    'margin_xof' => (int) $row->margin,
                    'margin_rate' => $revenue > 0 ? round((int) $row->margin / $revenue * 100, 2) : 0,
                ];
            })->values();
        });
    }

    public function recentInvoices(?int $limit = 10): Collection
    {
        $companyId = TenantContext::getCompanyId();

        return Cache::remember(
            self::CACHE_PREFIX . ".{$companyId}.recent_invoices.{$limit}",
            self::CACHE_TTL,
            fn () => Invoice::where('company_id', $companyId)
                ->with('customer:id,name')
                ->latest()
                ->take($limit)
                ->get(['id', 'number', 'customer_id', 'total_xof', 'status', 'issue_date'])
                ->map(fn (Invoice $inv) => [
                    'id' => $inv->id,
                    'number' => $inv->number,
                    'customer_name' => $inv->customer?->name,
                    'total_xof' => $inv->total_xof,
                    'status' => $inv->status->value,
                    'issue_date' => $inv->issue_date->format('Y-m-d'),
                ])
        );
    }

    public function expensesByCategory(): Collection
    {
        $companyId = TenantContext::getCompanyId();

        return Cache::remember(
            self::CACHE_PREFIX . ".{$companyId}.expenses_by_category",
            self::CACHE_TTL,
            fn () => Expense::where('company_id', $companyId)
                ->select('category', DB::raw('SUM(amount) as total_xof'), DB::raw('COUNT(*) as count'))
                ->groupBy('category')
                ->orderByDesc('total_xof')
                ->get()
        );
    }

    public function clearCache(): void
    {
        $companyId = TenantContext::getCompanyId();
        $prefix = self::CACHE_PREFIX . ".{$companyId}.";

        $keys = [
            "{$prefix}overview",
            "{$prefix}revenue_growth.12",
            "{$prefix}revenue_growth.6",
            "{$prefix}top_products.10",
            "{$prefix}top_customers.10",
            "{$prefix}recent_invoices.10",
            "{$prefix}expenses_by_category",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
