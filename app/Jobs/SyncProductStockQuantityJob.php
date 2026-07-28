<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductStockQuantityJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private readonly int $productId,
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (!$product) {
            return;
        }

        $totalQuantity = WarehouseStock::where('product_id', $product->id)->sum('quantity');
        $product->update(['quantity' => $totalQuantity]);
    }
}
