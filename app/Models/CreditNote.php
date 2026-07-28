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

class CreditNote extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'number',
        'status',
        'issue_date',
        'subtotal_xof',
        'tax_xof',
        'discount_xof',
        'total_xof',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'metadata' => 'array',
        'subtotal_xof' => 'integer',
        'tax_xof' => 'integer',
        'discount_xof' => 'integer',
        'total_xof' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CreditNote $creditNote) {
            if (empty($creditNote->uuid)) {
                $creditNote->uuid = (string) Str::uuid();
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
        return $this->hasMany(CreditNoteItem::class);
    }
}
