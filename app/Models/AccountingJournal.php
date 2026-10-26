<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingJournal extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class)->where('entry_type', 'debit');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class)->where('entry_type', 'credit');
    }
}
