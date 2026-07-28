<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Company
 * Represents the Tenant.
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'metadata',
        'plan_id',
        'manual_capital',
        'capital_updated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'capital_updated_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->uuid)) {
                $company->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Users belonging to this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
