<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrderItem extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'product_id',
        'product_variant_id',
        'name',
        'description',
        'quantity',
        'unit_price_xof',
        'tax_id',
        'tax_amount_xof',
        'discount_amount_xof',
        'subtotal_xof',
        'total_xof',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_xof' => 'integer',
        'tax_amount_xof' => 'integer',
        'discount_amount_xof' => 'integer',
        'subtotal_xof' => 'integer',
        'total_xof' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrderItem $item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
