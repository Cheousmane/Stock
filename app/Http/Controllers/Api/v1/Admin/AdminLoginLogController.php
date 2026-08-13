<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoginLogResource;
use App\Models\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLoginLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = LoginLog::with(['user', 'company'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('failed'), function ($query) {
                $query->where('success', false);
            })
            ->when($request->input('date_from'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->input('date_to'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->when($request->input('company_id'), function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return LoginLogResource::collection($logs)->response();
    }

    /**
     * Export filtered login logs to CSV.
     */
    public function export(Request $request)
    {
        $logs = LoginLog::with(['company'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('failed'), function ($query) {
                $query->where('success', false);
            })
            ->when($request->input('date_from'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->input('date_to'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->when($request->input('company_id'), function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AdminLoginLogsExport($logs),
            'connexions-' . date('Y-m-d-His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Summary: hourly logins (24h) and brute-force detection.
     */
    public function summary(): JsonResponse
    {
        $hourlyRaw = LoginLog::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as fail_count')
            )
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        $hourly = [];
        for ($h = 0; $h < 24; $h++) {
            $row = $hourlyRaw[$h] ?? null;
            $hourly[] = [
                'hour' => $h,
                'success' => $row ? (int) $row['success_count'] : 0,
                'failed' => $row ? (int) $row['fail_count'] : 0,
            ];
        }

        $bruteForceIps = LoginLog::select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('success', false)
            ->whereNotNull('ip_address')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('ip_address')
            ->having('attempts', '>=', 5)
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'ip_address' => $row->ip_address,
                    'attempts' => (int) $row->attempts,
                    'last_seen' => LoginLog::where('ip_address', $row->ip_address)
                        ->latest()
                        ->value('created_at'),
                ];
            });

        $total24h = (int) LoginLog::where('created_at', '>=', now()->subHours(24))->count();
        $failed24h = (int) LoginLog::where('success', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return response()->json([
            'hourly' => $hourly,
            'brute_force_ips' => $bruteForceIps,
            'total_24h' => $total24h,
            'failed_24h' => $failed24h,
        ]);
    }
}
