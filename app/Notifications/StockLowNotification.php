<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class StockLowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Product $product,
        public readonly WarehouseStock $stock,
        public readonly int $currentQuantity,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'warehouse_id' => $this->stock->warehouse_id,
            'warehouse_name' => $this->stock->warehouse?->name,
            'current_quantity' => $this->currentQuantity,
            'message' => "Stock low for {$this->product->name} in {$this->stock->warehouse?->name}. Current quantity: {$this->currentQuantity}.",
        ];
    }
}
