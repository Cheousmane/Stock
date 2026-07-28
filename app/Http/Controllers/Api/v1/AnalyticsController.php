<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function overview(AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_dashboard');

        return response()->json(['data' => $analytics->overview()], Response::HTTP_OK);
    }

    public function revenueGrowth(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_dashboard');

        $months = (int) ($request->input('months', 12));
        $months = max(1, min(60, $months));

        return response()->json(['data' => $analytics->revenueGrowth($months)], Response::HTTP_OK);
    }

    public function topProducts(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_dashboard');

        $limit = (int) ($request->input('limit', 10));
        $limit = max(1, min(100, $limit));

        return response()->json(['data' => $analytics->topProducts($limit)], Response::HTTP_OK);
    }

    public function topCustomers(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_dashboard');

        $limit = (int) ($request->input('limit', 10));
        $limit = max(1, min(100, $limit));

        return response()->json(['data' => $analytics->topCustomers($limit)], Response::HTTP_OK);
    }

    public function recentInvoices(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_invoice');

        $limit = (int) ($request->input('limit', 10));
        $limit = max(1, min(50, $limit));

        return response()->json(['data' => $analytics->recentInvoices($limit)], Response::HTTP_OK);
    }

    public function expensesByCategory(AnalyticsService $analytics): JsonResponse
    {
        $this->authorize('view_dashboard');

        return response()->json(['data' => $analytics->expensesByCategory()], Response::HTTP_OK);
    }
}
