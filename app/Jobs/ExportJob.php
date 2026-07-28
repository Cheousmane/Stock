<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\CustomersExport;
use App\Exports\InvoicesExport;
use App\Exports\ProductsExport;
use App\Exports\StockExport;
use App\Models\Export;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;

class ExportJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly Export $export,
    ) {}

    public function handle(): void
    {
        $this->export->update(['status' => 'processing']);

        try {
            $exporter = match ($this->export->type) {
                'products' => new ProductsExport(),
                'customers' => new CustomersExport(),
                'invoices' => new InvoicesExport(),
                'stock' => new StockExport(),
                default => throw new \InvalidArgumentException("Unknown export type: {$this->export->type}"),
            };

            $extension = 'xlsx';
            $type = ExcelType::XLSX;

            $fileName = $this->export->file_name ?? "{$this->export->type}-{$this->export->id}.{$extension}";
            $filePath = "exports/{$fileName}";

            Excel::store($exporter, $filePath, 'local', $type);

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
        }
    }
}
