<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Events\Dispatchable;

final class StockLow
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
        public readonly WarehouseStock $stock,
        public readonly int $currentQuantity,
    ) {}
}
