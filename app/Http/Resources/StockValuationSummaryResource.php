<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockValuationSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_value' => $this['total_value'],
            'total_quantity' => $this['total_quantity'],
            'products_count' => $this['products_count'],
            'valuation_method' => $this['valuation_method'],
            'by_warehouse' => $this['by_warehouse'] ?? [],
        ];
    }
}
