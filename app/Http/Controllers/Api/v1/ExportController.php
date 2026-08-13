<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Exports\CustomersExport;
use App\Exports\CreditNotesExport;
use App\Exports\DeliveryNotesExport;
use App\Exports\ExpensesExport;
use App\Exports\InvoicesExport;
use App\Exports\ProductsExport;
use App\Exports\PurchaseOrdersExport;
use App\Exports\QuotesExport;
use App\Exports\StockExport;
use App\Exports\SuppliersExport;
use App\Http\Controllers\Controller;
use App\Jobs\ExportJob;
use App\Models\Export;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function fileName(string $module, string $extension = 'xlsx'): string
    {
        $company = TenantContext::get();
        $slug = $company?->slug ?? 'unknown';
        $date = now()->format('Ymd');

        return sprintf('%s-%s-%s.%s', $module, $slug, $date, $extension);
    }

    public function exportProducts(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new ProductsExport(),
            $this->fileName('products')
        );
    }

    public function exportProductsCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new ProductsExport(),
            $this->fileName('products', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportCustomers(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new CustomersExport(),
            $this->fileName('customers')
        );
    }

    public function exportCustomersCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new CustomersExport(),
            $this->fileName('customers', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportInvoices(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new InvoicesExport(),
            $this->fileName('invoices')
        );
    }

    public function exportInvoicesCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new InvoicesExport(),
            $this->fileName('invoices', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportStock(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new StockExport(),
            $this->fileName('stock')
        );
    }

    public function exportDeliveryNotes(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new DeliveryNotesExport(),
            $this->fileName('delivery-notes')
        );
    }

    public function exportDeliveryNotesCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new DeliveryNotesExport(),
            $this->fileName('delivery-notes', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportExpenses(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new ExpensesExport(),
            $this->fileName('expenses')
        );
    }

    public function exportExpensesCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new ExpensesExport(),
            $this->fileName('expenses', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportQuotes(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new QuotesExport(),
            $this->fileName('quotes')
        );
    }

    public function exportQuotesCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new QuotesExport(),
            $this->fileName('quotes', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportCreditNotes(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new CreditNotesExport(),
            $this->fileName('credit-notes')
        );
    }

    public function exportCreditNotesCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new CreditNotesExport(),
            $this->fileName('credit-notes', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportSuppliers(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new SuppliersExport(),
            $this->fileName('suppliers')
        );
    }

    public function exportSuppliersCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new SuppliersExport(),
            $this->fileName('suppliers', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportPurchaseOrders(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new PurchaseOrdersExport(),
            $this->fileName('purchase-orders')
        );
    }

    public function exportPurchaseOrdersCsv(): BinaryFileResponse
    {
        $this->authorize('export_data');
        return Excel::download(
            new PurchaseOrdersExport(),
            $this->fileName('purchase-orders', 'csv'),
            ExcelType::CSV
        );
    }

    public function exportAsync(Request $request, string $type): JsonResponse
    {
        $this->authorize('export_data');

        $validTypes = ['products', 'customers', 'invoices', 'stock'];
        if (!in_array($type, $validTypes, true)) {
            return response()->json(['message' => 'Invalid export type'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $export = Export::create([
            'company_id' => TenantContext::getCompanyId(),
            'user_id' => $request->user()->id,
            'type' => $type,
            'status' => 'pending',
            'file_name' => $this->fileName($type),
        ]);

        ExportJob::dispatch($export);

        return response()->json([
            'export_id' => $export->id,
            'status' => 'pending',
            'message' => 'Export started. Check status via GET /exports/{id}/status',
        ], Response::HTTP_ACCEPTED);
    }

    public function status(Export $export): JsonResponse
    {
        $this->authorize('view', $export);

        return response()->json([
            'id' => $export->id,
            'type' => $export->type,
            'status' => $export->status,
            'file_name' => $export->file_name,
            'file_size' => $export->file_size,
            'error_message' => $export->error_message,
            'created_at' => $export->created_at,
            'completed_at' => $export->updated_at,
        ], Response::HTTP_OK);
    }

    public function download(Export $export): StreamedResponse|JsonResponse
    {
        $this->authorize('view', $export);

        if ($export->status !== 'completed' || !$export->file_path) {
            return response()->json(['message' => 'Export pas encore prêt'], Response::HTTP_CONFLICT);
        }

        if (!Storage::disk('local')->exists($export->file_path)) {
            return response()->json(['message' => 'Fichier introuvable'], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('local')->download($export->file_path, $export->file_name);
    }
}
