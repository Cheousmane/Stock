<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryNoteStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DeliveryNote extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'uuid',
        'number',
        'customer_id',
        'invoice_id',
        'status',
        'issue_date',
        'delivery_date',
        'notes',
        'signature',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'delivery_date' => 'date',
            'metadata' => 'array',
            'status' => DeliveryNoteStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DeliveryNote $deliveryNote) {
            if (empty($deliveryNote->uuid)) {
                $deliveryNote->uuid = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }
}
