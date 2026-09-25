<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Models\Invoice;
use App\Services\CapitalService;
use App\Support\DashboardCache;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('view_dashboard');
        $companyId = TenantContext::getCompanyId();

        $data = Cache::remember(DashboardCache::key($companyId), DashboardCache::TTL, function () use ($companyId) {
            $company = TenantContext::get();
            $capitalService = app(CapitalService::class);
            $capital = $capitalService->calculate($company);

            $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
            $driver = DB::getDriverName();
            $monthIssueExpr = $driver === 'sqlite' ? "strftime('%Y-%m', issue_date)" : "DATE_FORMAT(issue_date, '%Y-%m')";
            $monthCreatedExpr = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

            $revenueByMonth = Invoice::where('company_id', $companyId)
                ->where('status', InvoiceStatus::Paid)
                ->where('issue_date', '>=', $sixMonthsAgo)
                ->selectRaw("{$monthIssueExpr} as month, SUM(total_xof) as revenue")
                ->groupBy(DB::raw($monthIssueExpr))
                ->pluck('revenue', 'month');

            $posRevenueByMonth = DB::table('pos_sales')
                ->where('company_id', $companyId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $sixMonthsAgo)
                ->selectRaw("{$monthCreatedExpr} as month, SUM(total_xof) as revenue")
                ->groupBy(DB::raw($monthCreatedExpr))
                ->pluck('revenue', 'month');

            foreach ($posRevenueByMonth as $month => $revenue) {
                $revenueByMonth[$month] = ($revenueByMonth[$month] ?? 0) + (int) $revenue;
            }

            $revenueByMonth = collect($revenueByMonth)
                ->sortKeysDesc()
                ->map(fn (int $revenue, string $month) => [
                    'month' => $month,
                    'revenue' => $revenue,
                ])
                ->values();

            $aggregateQuery = DB::table('invoices')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->select(DB::raw("
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status IN ('sent', 'overdue', 'partial') THEN balance_due_xof ELSE 0 END) as outstanding,
                    SUM(CASE WHEN status IN ('paid', 'sent', 'partial') THEN total_xof ELSE 0 END) as total_revenue
                "))
                ->first();

            $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d H:i:s');
            $countsQuery = DB::select("
                SELECT
                    (SELECT COUNT(*) FROM products WHERE company_id = ? AND deleted_at IS NULL) as total_products,
                    (SELECT COUNT(*) FROM customers WHERE company_id = ? AND deleted_at IS NULL) as total_customers,
                    (SELECT COUNT(*) FROM payments WHERE company_id = ? AND created_at >= ?) as recent_payments,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ?) as total_expenses,
                    (SELECT COUNT(*) FROM expenses WHERE company_id = ? AND created_at >= ?) as recent_expenses,
                    (SELECT COUNT(*) FROM credit_notes WHERE company_id = ? AND deleted_at IS NULL) as total_credit_notes,
                    (SELECT COUNT(*) FROM credit_notes WHERE company_id = ? AND status = 'draft' AND deleted_at IS NULL) as draft_credit_notes,
                    (SELECT COALESCE(SUM(total_xof), 0) FROM credit_notes WHERE company_id = ? AND status = 'validated' AND deleted_at IS NULL) as credit_notes_amount
            ", [$companyId, $companyId, $companyId, $thirtyDaysAgo, $companyId, $companyId, $thirtyDaysAgo, $companyId, $companyId, $companyId])[0];

            $lowStockProducts = DB::table('products as p')
                ->where('p.company_id', $companyId)
                ->whereNull('p.deleted_at')
                ->where('p.min_stock', '>', 0)
                ->whereRaw('(SELECT COALESCE(SUM(ws.quantity), 0) FROM warehouse_stock ws WHERE ws.product_id = p.id) < p.min_stock')
                ->count();

            $aggregates = [
                'revenue_by_month' => $revenueByMonth,
                'total_invoices' => (int) $aggregateQuery->total_invoices,
                'outstanding' => (int) $aggregateQuery->outstanding,
                'total_revenue' => (int) $aggregateQuery->total_revenue,
                'total_products' => (int) $countsQuery->total_products,
                'total_customers' => (int) $countsQuery->total_customers,
                'recent_payments' => (int) $countsQuery->recent_payments,
                'total_expenses' => (int) $countsQuery->total_expenses,
                'recent_expenses' => (int) $countsQuery->recent_expenses,
                'total_credit_notes' => (int) $countsQuery->total_credit_notes,
                'draft_credit_notes' => (int) $countsQuery->draft_credit_notes,
                'credit_notes_amount' => (int) $countsQuery->credit_notes_amount,
                'low_stock_products' => $lowStockProducts,
            ];

            $recentInvoices = Invoice::with('customer:id,name')
                ->where('company_id', $companyId)
                ->latest()
                ->take(5)
                ->get(['id', 'number', 'customer_id', 'total_xof', 'status', 'issue_date']);

            $topProducts = DB::select("
                SELECT p.name, SUM(sub.total) as revenue
                FROM (
                    SELECT ii.product_id, ii.total_xof as total
                    FROM invoice_items ii
                    JOIN invoices i ON ii.invoice_id = i.id
                    WHERE i.company_id = ? AND i.status = ? AND i.deleted_at IS NULL
                    UNION ALL
                    SELECT psi.product_id, psi.subtotal_xof as total
                    FROM pos_sale_items psi
                    JOIN pos_sales ps ON psi.pos_sale_id = ps.id
                    WHERE ps.company_id = ? AND ps.status = 'completed'
                ) sub
                JOIN products p ON sub.product_id = p.id AND p.deleted_at IS NULL
                GROUP BY p.id, p.name
                ORDER BY revenue DESC
                LIMIT 5
            ", [$companyId, InvoiceStatus::Paid->value, $companyId]);

            return (object) [
                'total_revenue_xof' => $aggregates['total_revenue'],
                'outstanding' => $aggregates['outstanding'],
                'recent_payments' => $aggregates['recent_payments'],
                'total_expenses' => $aggregates['total_expenses'],
                'recent_expenses' => $aggregates['recent_expenses'],
                'total_invoices' => $aggregates['total_invoices'],
                'total_customers' => $aggregates['total_customers'],
                'total_products' => $aggregates['total_products'],
                'low_stock_products' => $aggregates['low_stock_products'],
                'capital' => $capital['calculated'],
                'total_credit_notes' => $aggregates['total_credit_notes'],
                'draft_credit_notes' => $aggregates['draft_credit_notes'],
                'credit_notes_amount' => $aggregates['credit_notes_amount'],
                'recent_invoices' => $recentInvoices,
                'revenue_by_month' => $aggregates['revenue_by_month'],
                'top_products' => $topProducts,
            ];
        });

        return response()->json(new DashboardResource($data), Response::HTTP_OK);
    }
}
