<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    public function findAll(array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Invoice;
    public function findByStatus(string $status, ?int $limit = null): Collection;
    public function store(array $data): Invoice;
    public function update(Invoice $invoice, array $data): Invoice;
    public function delete(Invoice $invoice): bool;
    public function getOverdueInvoices(?int $limit = null): Collection;
    public function getRevenueByMonth(int $year): Collection;
}
