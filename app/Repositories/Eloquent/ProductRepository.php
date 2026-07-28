<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function findAll(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['is_active'] ?? null, fn ($q, $v) => $q->where('is_active', $v))
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    public function store(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function getLowStock(?int $limit = null): Collection
    {
        return Product::query()
            ->whereColumn('min_stock', '>', \DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM warehouse_stock WHERE product_id = products.id)'))
            ->when($limit, fn ($q, $v) => $q->limit($v))
            ->get();
    }
}
