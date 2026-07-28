<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Export;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\DeliveryNote;
use App\Models\Company;
use App\Support\TenantContext;
use App\Services\InvoicePdfService;
use App\Services\QuotePdfService;
use App\Services\DeliveryNotePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly Export $export,
        private readonly Invoice|Quote|DeliveryNote $document,
    ) {}

    public function handle(
        InvoicePdfService $invoicePdf,
        QuotePdfService $quotePdf,
        DeliveryNotePdfService $deliveryNotePdf,
    ): void {
        $this->export->update(['status' => 'processing']);

        try {
            $company = Company::find($this->document->company_id);
            if ($company) {
                TenantContext::set($company);
            }

            $pdfContent = match ($this->export->type) {
                'invoice_pdf' => $invoicePdf->generate($this->document),
                'quote_pdf' => $quotePdf->generate($this->document),
                'delivery_note_pdf' => $deliveryNotePdf->generate($this->document),
                default => throw new \InvalidArgumentException("Unknown PDF type: {$this->export->type}"),
            };

            $fileName = $this->export->file_name ?? "{$this->export->type}-{$this->document->id}.pdf";
            $filePath = "pdfs/{$fileName}";

            Storage::disk('local')->put($filePath, $pdfContent);

            $fullPath = Storage::disk('local')->path($filePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            $this->export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ]);
        } catch (\Throwable $e) {
            $this->export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        } finally {
            TenantContext::clear();
        }
    }
}
