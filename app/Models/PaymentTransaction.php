<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'payment_link_id',
        'gateway',
        'transaction_reference',
        'external_reference',
        'amount_xof',
        'currency',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'request_payload',
        'response_payload',
        'webhook_payload',
        'error_message',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_xof' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'webhook_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }
}
