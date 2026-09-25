<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'number',
        'customer_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal_xof',
        'tax_xof',
        'discount_xof',
        'discount_type',
        'total_xof',
        'paid_xof',
        'balance_due_xof',
        'notes',
        'terms',
        'metadata',
        'fiscal_regime',
        'tax_id_number',
        'fiscal_reference',
        'is_fiscal',
        'fiscal_issue_date',
        'fiscal_metadata',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal_xof' => 'integer',
            'tax_xof' => 'integer',
            'discount_xof' => 'integer',
            'total_xof' => 'integer',
            'paid_xof' => 'integer',
            'balance_due_xof' => 'integer',
            'metadata' => 'array',
            'status' => InvoiceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
