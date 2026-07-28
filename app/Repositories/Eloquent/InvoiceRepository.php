<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function findAll(array $filters = []): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['customer', 'items'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '<=', $v))
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): ?Invoice
    {
        return Invoice::with(['customer', 'items'])->find($id);
    }

    public function findByStatus(string $status, ?int $limit = null): Collection
    {
        return Invoice::with(['customer', 'items'])
            ->where('status', $status)
            ->when($limit, fn ($q, $v) => $q->limit($v))
            ->get();
    }

    public function store(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }

    public function delete(Invoice $invoice): bool
    {
        return (bool) $invoice->delete();
    }

    public function getOverdueInvoices(?int $limit = null): Collection
    {
        return Invoice::with(['customer'])
            ->where('status', 'overdue')
            ->where('due_date', '<', now())
            ->when($limit, fn ($q, $v) => $q->limit($v))
            ->get();
    }

    public function getRevenueByMonth(int $year): Collection
    {
        return Invoice::query()
            ->whereYear('issue_date', $year)
            ->where('status', 'paid')
            ->select(
                DB::raw('MONTH(issue_date) as month'),
                DB::raw('SUM(total_xof) as revenue')
            )
            ->groupBy(DB::raw('MONTH(issue_date)'))
            ->orderBy(DB::raw('MONTH(issue_date)'))
            ->get();
    }
}
