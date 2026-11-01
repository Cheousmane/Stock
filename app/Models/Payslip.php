<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payslip extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'company_id', 'payroll_run_id', 'employee_id',
        'base_salary_xof', 'gross_salary_xof', 'deductions_xof', 'net_salary_xof',
        'lines', 'status', 'expense_id',
    ];

    protected function casts(): array
    {
        return [
            'base_salary_xof' => 'integer',
            'gross_salary_xof' => 'integer',
            'deductions_xof' => 'integer',
            'net_salary_xof' => 'integer',
            'lines' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payslip $payslip) {
            if (empty($payslip->uuid)) {
                $payslip->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
