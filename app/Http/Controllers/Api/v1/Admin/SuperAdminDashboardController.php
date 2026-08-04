<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminDashboardResource;
use App\Models\Company;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class SuperAdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('super_admin.dashboard', 120, function () {
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

            return (object) [
                'total_companies' => $totalCompanies,
                'active_companies' => $activeCompanies,
                'suspended_companies' => $suspendedCompanies,
                'total_users' => $totalUsers,
                'total_revenue' => $subscriptionRevenue,
                'paying_companies' => $payingCompanies,
                'total_invoices' => $totalInvoices,
                'recent_logins' => $recentLogins,
                'failed_logins' => $failedLogins,
                'logins_today' => $loginsToday,
                'recent_companies' => $recentCompanies,
                'chart_registrations' => $chartRegistrations,
                'chart_logins' => $chartLogins,
            ];
        });

        return response()->json(new AdminDashboardResource($data), Response::HTTP_OK);
    }
}
