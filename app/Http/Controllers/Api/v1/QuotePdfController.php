<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePdfJob;
use App\Models\Export;
use App\Models\Quote;
use App\Services\QuotePdfService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class QuotePdfController extends Controller
{
    public function download(Quote $quote, QuotePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $quote);
        $pdf = $service->generate($quote);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="devis-' . $quote->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function preview(Quote $quote, QuotePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $quote);
        $pdf = $service->generate($quote);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="devis-' . $quote->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function exportAsync(Quote $quote, Request $request): JsonResponse
    {
        $this->authorize('view', $quote);

        $export = Export::create([
            'company_id' => TenantContext::getCompanyId(),
            'user_id' => $request->user()->id,
            'type' => 'quote_pdf',
            'status' => 'pending',
            'file_name' => "devis-{$quote->number}.pdf",
            'exportable_type' => $quote->getMorphClass(),
            'exportable_id' => $quote->id,
        ]);

        GeneratePdfJob::dispatch($export, $quote);

        return response()->json([
            'export_id' => $export->id,
            'status' => 'pending',
        ], SymfonyResponse::HTTP_ACCEPTED);
    }
}
