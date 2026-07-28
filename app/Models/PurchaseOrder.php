<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'warehouse_id',
        'number',
        'status',
        'issue_date',
        'expected_delivery_date',
        'subtotal_xof',
        'tax_xof',
        'discount_xof',
        'discount_type',
        'total_xof',
        'paid_xof',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expected_delivery_date' => 'date',
        'metadata' => 'array',
        'subtotal_xof' => 'integer',
        'tax_xof' => 'integer',
        'discount_xof' => 'integer',
        'total_xof' => 'integer',
        'paid_xof' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $po) {
            if (empty($po->uuid)) {
                $po->uuid = (string) Str::uuid();
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
