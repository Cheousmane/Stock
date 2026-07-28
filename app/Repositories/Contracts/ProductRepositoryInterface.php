<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function findAll(array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Product;
    public function findBySku(string $sku): ?Product;
    public function store(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): bool;
    public function getLowStock(?int $limit = null): Collection;
}
