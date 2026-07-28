<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StockLow;
use App\Models\User;
use App\Notifications\StockLowNotification;
use App\Services\WebhookDispatcherService;

final class NotifyStockLow
{
    public function handle(StockLow $event): void
    {
        $company = $event->product->company;

        if ($company === null) {
            return;
        }

        $adminUsers = $company->users()
            ->role(['admin', 'super-admin'])
            ->get();

        if ($adminUsers->isNotEmpty()) {
            $notification = new StockLowNotification(
                $event->product,
                $event->stock,
                $event->currentQuantity,
            );

            foreach ($adminUsers as $user) {
                $user->notify($notification);
            }
        }

        app(WebhookDispatcherService::class)->dispatch('stock.low', [
            'product_id' => $event->product->id,
            'product_name' => $event->product->name,
            'product_sku' => $event->product->sku,
            'current_quantity' => $event->currentQuantity,
            'min_stock' => $event->product->min_stock,
            'warehouse_id' => $event->stock?->warehouse_id,
        ]);
    }
}
