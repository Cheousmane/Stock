<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Exports\AdminActivityLogsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class AdminActivityLogController extends Controller
{
    /**
     * Display a listing of all activity logs across all companies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'causer.company', 'subject']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('log_name') && $request->log_name !== '') {
            $query->where('log_name', $request->log_name);
        }

        if ($request->has('event') && $request->event !== '') {
            $query->where('event', $request->event);
        }

        if ($request->has('company_id') && $request->company_id !== '') {
            $query->where('company_id', $request->company_id);
        }

        $logs = $query->latest()->paginate($request->get('per_page', 50));

        return response()->json($logs, Response::HTTP_OK);
    }

    /**
     * Export filtered activity logs to CSV.
     */
    public function export(Request $request)
    {
        $query = Activity::with(['causer', 'causer.company', 'subject']);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('event') && $request->event !== '') {
            $query->where('event', $request->event);
        }

        if ($request->has('company_id') && $request->company_id !== '') {
            $query->where('company_id', $request->company_id);
        }

        $logs = $query->limit(10000)->get();

        return Excel::download(
            new AdminActivityLogsExport($logs),
            'activity-logs-' . date('Y-m-d-His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
