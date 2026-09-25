<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'token',
        'amount_xof',
        'status',
        'allowed_gateways',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_xof' => 'integer',
            'allowed_gateways' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentLink $link) {
            if (empty($link->token)) {
                $link->token = Str::random(32);
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getUrlAttribute(): string
    {
        return url('/pay/' . $this->token);
    }
}
