<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'quote_id',
        'product_id',
        'description',
        'quantity',
        'unit_price_xof',
        'unit_cost_xof',
        'subtotal_xof',
        'tax_rate',
        'tax_xof',
        'total_xof',
        'total_cost_xof',
        'margin_xof',
        'margin_rate',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_xof' => 'integer',
            'unit_cost_xof' => 'integer',
            'subtotal_xof' => 'integer',
            'tax_rate' => 'decimal:2',
            'tax_xof' => 'integer',
            'total_xof' => 'integer',
            'total_cost_xof' => 'integer',
            'margin_xof' => 'integer',
            'margin_rate' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
