<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePdfJob;
use App\Models\DeliveryNote;
use App\Models\Export;
use App\Services\DeliveryNotePdfService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DeliveryNotePdfController extends Controller
{
    public function download(DeliveryNote $deliveryNote, DeliveryNotePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $deliveryNote);
        $pdf = $service->generate($deliveryNote);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bl-' . $deliveryNote->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function preview(DeliveryNote $deliveryNote, DeliveryNotePdfService $service): SymfonyResponse
    {
        $this->authorize('view', $deliveryNote);
        $pdf = $service->generate($deliveryNote);

        return new Response(
            $pdf,
            SymfonyResponse::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="bl-' . $deliveryNote->number . '.pdf"',
                'Content-Length' => strlen($pdf),
            ]
        );
    }

    public function exportAsync(DeliveryNote $deliveryNote, Request $request): JsonResponse
    {
        $this->authorize('view', $deliveryNote);

        $export = Export::create([
            'company_id' => TenantContext::getCompanyId(),
            'user_id' => $request->user()->id,
            'type' => 'delivery_note_pdf',
            'status' => 'pending',
            'file_name' => "bl-{$deliveryNote->number}.pdf",
            'exportable_type' => $deliveryNote->getMorphClass(),
            'exportable_id' => $deliveryNote->id,
        ]);

        GeneratePdfJob::dispatch($export, $deliveryNote);

        return response()->json([
            'export_id' => $export->id,
            'status' => 'pending',
        ], SymfonyResponse::HTTP_ACCEPTED);
    }
}
