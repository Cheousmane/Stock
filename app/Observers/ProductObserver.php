<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;

final class ProductObserver
{
    public function created(Product $product): void
    {
        activity()
            ->performedOn($product)
            ->withProperties([
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
            ])
            ->event('created')
            ->log('Product created');
    }

    public function updated(Product $product): void
    {
        $changed = $product->getChanges();

        activity()
            ->performedOn($product)
            ->withProperties([
                'changed_attributes' => $changed,
            ])
            ->event('updated')
            ->log('Product updated');
    }

    public function deleted(Product $product): void
    {
        activity()
            ->performedOn($product)
            ->withProperties([
                'name' => $product->name,
                'sku' => $product->sku,
            ])
            ->event('deleted')
            ->log('Product deleted');
    }
}
