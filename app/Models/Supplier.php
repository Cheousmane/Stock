<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'tax_number',
        'registration_number',
        'notes',
        'is_active',
        'balance_xof',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance_xof' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (empty($supplier->uuid)) {
                $supplier->uuid = (string) Str::uuid();
            }
            if (empty($supplier->code)) {
                $maxId = static::withTrashed()
                    ->where('company_id', $supplier->company_id)
                    ->max('id') ?? 0;
                $supplier->code = 'SUP-' . str_pad((string) ($maxId + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
