<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ProfitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfitController extends Controller
{
    public function summary(Request $request, ProfitService $profitService): JsonResponse
    {
        $this->authorize('view_profits');

        $data = $profitService->getSummary(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function byProduct(Request $request, ProfitService $profitService): JsonResponse
    {
        $this->authorize('view_profits');

        $data = $profitService->getByProduct(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function byCustomer(Request $request, ProfitService $profitService): JsonResponse
    {
        $this->authorize('view_profits');

        $data = $profitService->getByCustomer(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function byInvoice(Request $request, ProfitService $profitService): JsonResponse
    {
        $this->authorize('view_profits');

        $data = $profitService->getByInvoice(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        return response()->json(['data' => $data], Response::HTTP_OK);
    }

    public function recalculate(Invoice $invoice, ProfitService $profitService): JsonResponse
    {
        $this->authorize('update', $invoice);

        $profitService->calculateInvoiceProfit($invoice);
        $invoice->load('items');

        return response()->json(['message' => 'Profit recalculated'], Response::HTTP_OK);
    }
}
