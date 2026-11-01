<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PayrollRun extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id', 'period', 'period_start', 'period_end', 'status',
        'total_gross_xof', 'total_net_xof', 'total_deductions_xof',
        'created_by', 'validated_at', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_gross_xof' => 'integer',
            'total_net_xof' => 'integer',
            'total_deductions_xof' => 'integer',
            'validated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollRun $run) {
            if (empty($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
