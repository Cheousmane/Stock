<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingAccount extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'subtype',
        'is_active',
        'parent_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): ?BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'account_id')->where('entry_type', 'debit');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'account_id')->where('entry_type', 'credit');
    }
}