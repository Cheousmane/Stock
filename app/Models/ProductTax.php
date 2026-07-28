<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductTax extends Pivot
{
    protected $fillable = [
        'product_id',
        'tax_id',
    ];
}
