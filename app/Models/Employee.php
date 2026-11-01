<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Employee extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id', 'first_name', 'last_name', 'email', 'phone',
        'position', 'department', 'contract_type', 'hire_date', 'end_date',
        'base_salary_xof', 'payment_method', 'bank_name', 'bank_account',
        'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'base_salary_xof' => 'integer',
            'hire_date' => 'date',
            'end_date' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
