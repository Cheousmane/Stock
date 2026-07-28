<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePdfJob;
use App\Models\Export;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoicePdfController extends Controller
{
    public function download(Invoice $invoice, InvoicePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $invoice);
        $pdf = $service->generate($invoice);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-' . $invoice->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function preview(Invoice $invoice, InvoicePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $invoice);
        $pdf = $service->generate($invoice);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture-' . $invoice->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function exportAsync(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorize('view', $invoice);

        $export = Export::create([
            'company_id' => TenantContext::getCompanyId(),
            'user_id' => $request->user()->id,
            'type' => 'invoice_pdf',
            'status' => 'pending',
            'file_name' => "facture-{$invoice->number}.pdf",
            'exportable_type' => $invoice->getMorphClass(),
            'exportable_id' => $invoice->id,
        ]);

        GeneratePdfJob::dispatch($export, $invoice);

        return response()->json([
            'export_id' => $export->id,
            'status' => 'pending',
        ], SymfonyResponse::HTTP_ACCEPTED);
    }
}
