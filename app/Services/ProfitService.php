<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    public function getItemCost(InvoiceItem $item): int
    {
        $product = $item->product;

        if ($product === null) {
            return 0;
        }

        $stockValuationService = app(StockValuationService::class);

        $invoice = $item->invoice;
        $defaultWarehouse = \App\Models\Warehouse::where('company_id', TenantContext::getCompanyId())
            ->orderBy('id')
            ->first();

        if ($defaultWarehouse) {
            $avgCost = $stockValuationService->getWeightedAverageCost($product, $defaultWarehouse);
            if ($avgCost !== null) {
                return $avgCost;
            }
        }

        if ($product->cost_price_xof > 0) {
            return $product->cost_price_xof;
        }

        if ($product->purchase_price_xof > 0) {
            return $product->purchase_price_xof;
        }

        return 0;
    }

    public function calculateItemProfit(InvoiceItem $item): InvoiceItem
    {
        $unitCost = $this->getItemCost($item);
        $quantity = $item->quantity;
        $unitPrice = $item->unit_price_xof;
        $totalCost = $unitCost * $quantity;
        $margin = ($unitPrice * $quantity) - $totalCost;
        $marginRate = $unitPrice > 0 ? round(($unitPrice - $unitCost) / $unitPrice * 100, 2) : 0;

        $item->unit_cost_xof = $unitCost;
        $item->total_cost_xof = $totalCost;
        $item->margin_xof = $margin;
        $item->margin_rate = $marginRate;
        $item->save();

        return $item;
    }

    public function calculateInvoiceProfit(Invoice $invoice): void
    {
        $invoice->loadMissing('items.product');

        foreach ($invoice->items as $item) {
            $this->calculateItemProfit($item);
        }

        $invoice->unsetRelation('items');
    }

    public function getSummary(?int $companyId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $companyId = $companyId ?? TenantContext::getCompanyId();

        $query = InvoiceItem::where('invoice_items.company_id', $companyId)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['paid', 'sent']);

        if ($startDate) {
            $query->whereDate('invoices.issue_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('invoices.issue_date', '<=', $endDate);
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(invoice_items.unit_price_xof * invoice_items.quantity), 0) as total_revenue_xof'),
            DB::raw('COALESCE(SUM(invoice_items.total_cost_xof), 0) as total_cost_xof'),
            DB::raw('COALESCE(SUM(invoice_items.margin_xof), 0) as total_margin_xof'),
            DB::raw('COUNT(invoice_items.id) as item_count'),
            DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as invoice_count'),
        )->first();

        $totalRevenue = (int) $result->total_revenue_xof;
        $totalCost = (int) $result->total_cost_xof;
        $totalMargin = (int) $result->total_margin_xof;

        $avgMarginRate = $totalRevenue > 0
            ? round($totalMargin / $totalRevenue * 100, 2)
            : 0;

        return [
            'total_revenue_xof' => $totalRevenue,
            'total_cost_xof' => $totalCost,
            'total_margin_xof' => $totalMargin,
            'average_margin_rate' => $avgMarginRate,
            'item_count' => (int) $result->item_count,
            'invoice_count' => (int) $result->invoice_count,
        ];
    }

    public function getByProduct(?int $companyId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $companyId = $companyId ?? TenantContext::getCompanyId();

        $results = InvoiceItem::where('invoice_items.company_id', $companyId)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['paid', 'sent'])
            ->whereNotNull('invoice_items.product_id')
            ->when($startDate, fn ($q) => $q->whereDate('invoices.issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('invoices.issue_date', '<=', $endDate))
            ->groupBy('invoice_items.product_id')
            ->select(
                'invoice_items.product_id',
                DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity) as revenue'),
                DB::raw('SUM(invoice_items.total_cost_xof) as cost'),
                DB::raw('SUM(invoice_items.margin_xof) as margin'),
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
            )
            ->get()
            ->keyBy('product_id');

        $products = Product::whereIn('id', $results->pluck('product_id'))
            ->get(['id', 'name', 'sku'])
            ->keyBy('id');

        return $results->map(function ($row) use ($products) {
            $product = $products->get($row->product_id);
            $revenue = (int) $row->revenue;

            return [
                'product_id' => $row->product_id,
                'product_name' => $product?->name ?? 'Deleted Product',
                'product_sku' => $product?->sku ?? '',
                'total_quantity' => (int) $row->total_quantity,
                'revenue_xof' => $revenue,
                'cost_xof' => (int) $row->cost,
                'margin_xof' => (int) $row->margin,
                'margin_rate' => $revenue > 0 ? round((int) $row->margin / $revenue * 100, 2) : 0,
            ];
        })->sortByDesc('margin_xof')->values();
    }

    public function getByCustomer(?int $companyId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $companyId = $companyId ?? TenantContext::getCompanyId();

        $results = InvoiceItem::where('invoice_items.company_id', $companyId)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['paid', 'sent'])
            ->when($startDate, fn ($q) => $q->whereDate('invoices.issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('invoices.issue_date', '<=', $endDate))
            ->groupBy('invoices.customer_id')
            ->select(
                'invoices.customer_id',
                DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity) as revenue'),
                DB::raw('SUM(invoice_items.total_cost_xof) as cost'),
                DB::raw('SUM(invoice_items.margin_xof) as margin'),
                DB::raw('COUNT(DISTINCT invoices.id) as invoice_count'),
            )
            ->get()
            ->keyBy('customer_id');

        $customers = \App\Models\Customer::whereIn('id', $results->pluck('customer_id'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return $results->map(function ($row) use ($customers) {
            $customer = $customers->get($row->customer_id);
            $revenue = (int) $row->revenue;

            return [
                'customer_id' => $row->customer_id,
                'customer_name' => $customer?->name ?? 'Deleted Customer',
                'customer_email' => $customer?->email ?? '',
                'invoice_count' => (int) $row->invoice_count,
                'revenue_xof' => $revenue,
                'cost_xof' => (int) $row->cost,
                'margin_xof' => (int) $row->margin,
                'margin_rate' => $revenue > 0 ? round((int) $row->margin / $revenue * 100, 2) : 0,
            ];
        })->sortByDesc('margin_xof')->values();
    }

    public function getByInvoice(?int $companyId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $companyId = $companyId ?? TenantContext::getCompanyId();

        $results = InvoiceItem::where('invoice_items.company_id', $companyId)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['paid', 'sent'])
            ->when($startDate, fn ($q) => $q->whereDate('invoices.issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('invoices.issue_date', '<=', $endDate))
            ->groupBy('invoice_items.invoice_id')
            ->select(
                'invoice_items.invoice_id',
                DB::raw('SUM(invoice_items.unit_price_xof * invoice_items.quantity) as revenue'),
                DB::raw('SUM(invoice_items.total_cost_xof) as cost'),
                DB::raw('SUM(invoice_items.margin_xof) as margin'),
                DB::raw('COUNT(invoice_items.id) as item_count'),
            )
            ->get()
            ->keyBy('invoice_id');

        $invoices = Invoice::whereIn('id', $results->pluck('invoice_id'))
            ->with('customer:id,name')
            ->get(['id', 'number', 'customer_id', 'issue_date'])
            ->keyBy('id');

        return $results->map(function ($row) use ($invoices) {
            $invoice = $invoices->get($row->invoice_id);
            $revenue = (int) $row->revenue;

            return [
                'invoice_id' => $row->invoice_id,
                'invoice_number' => $invoice?->number ?? 'N/A',
                'customer_name' => $invoice?->customer?->name ?? 'N/A',
                'issue_date' => $invoice?->issue_date?->format('Y-m-d') ?? '',
                'item_count' => (int) $row->item_count,
                'revenue_xof' => $revenue,
                'cost_xof' => (int) $row->cost,
                'margin_xof' => (int) $row->margin,
                'margin_rate' => $revenue > 0 ? round((int) $row->margin / $revenue * 100, 2) : 0,
            ];
        })->sortByDesc('margin_xof')->values();
    }
}
