<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosSale extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'pos_session_id',
        'user_id',
        'customer_id',
        'receipt_number',
        'subtotal_xof',
        'tax_xof',
        'discount_xof',
        'total_xof',
        'payment_method',
        'amount_paid_xof',
        'change_returned_xof',
        'status',
    ];

    protected $casts = [
        'subtotal_xof' => 'integer',
        'tax_xof' => 'integer',
        'discount_xof' => 'integer',
        'total_xof' => 'integer',
        'amount_paid_xof' => 'integer',
        'change_returned_xof' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class, 'pos_sale_id');
    }
}
