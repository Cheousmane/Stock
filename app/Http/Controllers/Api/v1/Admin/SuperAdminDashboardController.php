<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminDashboardResource;
use App\Models\Company;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\AdminDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        /*
         * Performance strategy:
         * - The "static" part (companies, users, MRR, invoiced volume, alerts
         *   based on companies) is cached for 300s under a versioned key.
         * - Any write on Company / User / Invoice bumps the version via model
         *   observers (CompanyObserver, UserObserver, InvoiceObserver), so the
         *   cache is invalidated instantly and rebuilt on next request.
         * - Login-related data (counts, recent list, charts) is computed live:
         *   it changes on every authentication and would otherwise make the
         *   cache useless.
         */
        $static = AdminDashboardCache::remember(function () {
            $companies = DB::table('companies');

            $totalCompanies = (clone $companies)->count();
            $activeCompanies = (clone $companies)->where('status', 'active')->count();
            $suspendedCompanies = (clone $companies)->where('status', 'suspended')->count();

            $totalUsers = User::count();

            // SaaS revenue: monthly recurring revenue from subscriptions (plans), not tenant sales.
            $subscriptionRevenueQuery = function () {
                return DB::table('companies')
                    ->join('plans', 'plans.id', '=', 'companies.plan_id')
                    ->where('plans.price_xof', '>', 0)
                    ->whereIn('companies.status', ['active', 'trial']);
            };

            $subscriptionRevenue = (int) $subscriptionRevenueQuery()->sum('plans.price_xof');

            $payingCompanies = $subscriptionRevenueQuery()->count();

            $totalInvoices = DB::table('invoices')
                ->whereNull('deleted_at')
                ->count();

            // Health KPIs
            $trialExpiredCompanies = DB::table('companies')
                ->where('status', 'trial')
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<', now())
                ->count();

            $trialCompanies = DB::table('companies')->where('status', 'trial')->count();

            $activeUsers = User::where('is_active', true)->count();
            $adoptionRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;

            $invoicedVolume30d = (int) DB::table('invoices')
                ->whereNull('deleted_at')
                ->where('status', '!=', 'cancelled')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('total_xof');

            $invoicesCount30d = (int) DB::table('invoices')
                ->whereNull('deleted_at')
                ->where('status', '!=', 'cancelled')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            // Top companies by invoiced volume (30 days)
            $topCompanies = DB::table('invoices')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->whereNull('invoices.deleted_at')
                ->where('invoices.status', '!=', 'cancelled')
                ->where('invoices.created_at', '>=', now()->subDays(30))
                ->select(
                    'companies.id',
                    'companies.name',
                    'companies.status',
                    DB::raw('SUM(invoices.total_xof) as volume'),
                    DB::raw('COUNT(*) as invoices_count')
                )
                ->groupBy('companies.id', 'companies.name', 'companies.status')
                ->orderByDesc('volume')
                ->limit(10)
                ->get();

            // Alerts (login-based alerts are computed live below)
            $alerts = [];

            if ($trialExpiredCompanies > 0) {
                $alerts[] = [
                    'severity' => 'warning',
                    'title' => 'Essais expirés',
                    'message' => "{$trialExpiredCompanies} entreprise(s) ont dépassé leur période d'essai.",
                    'link' => '/admin/companies?status=trial',
                ];
            }

            $suspendedRecently = (int) DB::table('companies')
                ->where('status', 'suspended')
                ->where('suspended_at', '>=', now()->subDays(7))
                ->count();

            if ($suspendedRecently > 0) {
                $alerts[] = [
                    'severity' => 'danger',
                    'title' => 'Suspensions récentes',
                    'message' => "{$suspendedRecently} entreprise(s) suspendue(s) ces 7 derniers jours.",
                    'link' => '/admin/companies?status=suspended',
                ];
            }

            $unpaidByUsers = (int) DB::table('companies')
                ->whereNull('plan_id')
                ->where('status', 'active')
                ->count();

            if ($unpaidByUsers > 0 && $trialCompanies === 0) {
                $alerts[] = [
                    'severity' => 'info',
                    'title' => 'Sans plan',
                    'message' => "{$unpaidByUsers} entreprise(s) active(s) sans plan associé.",
                    'link' => '/admin/companies',
                ];
            }

            $recentCompanies = Company::withCount('users')
                ->latest()
                ->take(10)
                ->get();

            // Chart data: Registrations over last 30 days
            $chartRegistrations = [];
            $registrationsRaw = Company::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get()
                ->pluck('count', 'date')
                ->toArray();

            for ($i = 29; $i >= 0; $i--) {
                $dateStr = now()->subDays($i)->format('Y-m-d');
                $chartRegistrations[] = [
                    'date' => $dateStr,
                    'count' => $registrationsRaw[$dateStr] ?? 0,
                ];
            }

            return (object) [
                'total_companies' => $totalCompanies,
                'active_companies' => $activeCompanies,
                'suspended_companies' => $suspendedCompanies,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'adoption_rate' => $adoptionRate,
                'total_revenue' => $subscriptionRevenue,
                'paying_companies' => $payingCompanies,
                'total_invoices' => $totalInvoices,
                'trial_companies' => $trialCompanies,
                'trial_expired_companies' => $trialExpiredCompanies,
                'invoiced_volume_30d' => $invoicedVolume30d,
                'invoices_count_30d' => $invoicesCount30d,
                'top_companies' => $topCompanies,
                'alerts' => $alerts,
                'recent_companies' => $recentCompanies,
                'chart_registrations' => $chartRegistrations,
            ];
        });

        // --- Live part: login activity (changes on every authentication) ---

        $recentLogins = LoginLog::where('success', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $failedLogins = LoginLog::where('success', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $loginsToday = LoginLog::with('user', 'company')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->take(20)
            ->get();

        $bruteForceIps = DB::table('login_logs')
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('success', false)
            ->whereNotNull('ip_address')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('ip_address')
            ->having('attempts', '>=', 10)
            ->orderByDesc('attempts')
            ->limit(5)
            ->get();

        if ($bruteForceIps->isNotEmpty()) {
            $topIp = $bruteForceIps->first();
            $static->alerts[] = [
                'severity' => 'critical',
                'title' => 'Tentatives de brute-force',
                'message' => "IP {$topIp->ip_address} : {$topIp->attempts} échecs en 24h (total: " . $bruteForceIps->count() . " IP suspectes).",
                'link' => '/admin/login-logs?failed=1',
            ];
        }

        // Chart data: Logins (success vs failures)
        $loginsStatsRaw = LoginLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as fail_count')
            )
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->keyBy('date')
            ->toArray();

        $chartLogins = [];
        for ($i = 13; $i >= 0; $i--) {
            $dateStr = now()->subDays($i)->format('Y-m-d');
            $row = $loginsStatsRaw[$dateStr] ?? null;
            $chartLogins[] = [
                'date' => $dateStr,
                'success' => $row ? (int) $row['success_count'] : 0,
                'failed' => $row ? (int) $row['fail_count'] : 0,
            ];
        }

        $static->recent_logins = $recentLogins;
        $static->failed_logins = $failedLogins;
        $static->logins_today = $loginsToday;
        $static->chart_logins = $chartLogins;

        return (new AdminDashboardResource($static))->response();
    }
}
