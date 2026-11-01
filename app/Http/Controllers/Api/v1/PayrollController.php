<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    // ---------- Employees ----------
    public function index(Request $request): JsonResponse
    {
        $companyId = TenantContext::getCompanyId();
        $employees = Employee::where('company_id', $companyId)
            ->orderBy('last_name')
            ->get();

        return response()->json(['data' => $employees], Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = TenantContext::getCompanyId();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', 'string', 'in:cdi,cdd,stage,freelance'],
            'hire_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'base_salary_xof' => ['required', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:bank,cash,mobile_money'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,suspended,terminated'],
        ]);

        $employee = Employee::create(array_merge($validated, ['company_id' => $companyId]));

        return response()->json(['data' => $employee], Response::HTTP_CREATED);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $employee], Response::HTTP_OK);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', 'string', 'in:cdi,cdd,stage,freelance'],
            'hire_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'base_salary_xof' => ['sometimes', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:bank,cash,mobile_money'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,suspended,terminated'],
        ]);

        $employee->update($validated);

        return response()->json(['data' => $employee->fresh()], Response::HTTP_OK);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    // ---------- Payroll runs ----------
    public function runsIndex(): JsonResponse
    {
        $companyId = TenantContext::getCompanyId();
        $runs = PayrollRun::where('company_id', $companyId)
            ->withCount('payslips')
            ->orderByDesc('period')
            ->get();

        return response()->json(['data' => $runs], Response::HTTP_OK);
    }

    public function runsStore(Request $request): JsonResponse
    {
        $companyId = TenantContext::getCompanyId();
        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $run = PayrollRun::create([
            'company_id' => $companyId,
            'period' => $validated['period'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => 'draft',
            'created_by' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Génère un bulletin pour chaque employé actif.
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        $totalGross = 0;
        $totalNet = 0;
        $totalDeductions = 0;

        foreach ($employees as $employee) {
            $gross = (int) $employee->base_salary_xof;
            $deductions = 0;
            $net = $gross - $deductions;

            Payslip::create([
                'company_id' => $companyId,
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'base_salary_xof' => $employee->base_salary_xof,
                'gross_salary_xof' => $gross,
                'deductions_xof' => $deductions,
                'net_salary_xof' => $net,
                'lines' => [
                    ['label' => 'Salaire de base', 'amount' => $gross, 'type' => 'earning'],
                ],
                'status' => 'draft',
            ]);

            $totalGross += $gross;
            $totalNet += $net;
            $totalDeductions += $deductions;
        }

        $run->update([
            'total_gross_xof' => $totalGross,
            'total_net_xof' => $totalNet,
            'total_deductions_xof' => $totalDeductions,
        ]);

        return response()->json(['data' => $run->fresh(['payslips.employee'])], Response::HTTP_CREATED);
    }

    public function runsShow(int $payrollRun): JsonResponse
    {
        $run = PayrollRun::withoutGlobalScopes()
            ->where('company_id', TenantContext::getCompanyId())
            ->findOrFail($payrollRun);

        return response()->json(['data' => $run->load(['payslips.employee'])], Response::HTTP_OK);
    }

    public function runsValidate(int $payrollRun): JsonResponse
    {
        $run = PayrollRun::withoutGlobalScopes()
            ->where('company_id', TenantContext::getCompanyId())
            ->findOrFail($payrollRun);

        if ($run->status !== 'draft') {
            return response()->json(['message' => 'Cette paie a déjà été validée.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $run->update([
            'status' => 'validated',
            'validated_at' => now(),
        ]);

        return response()->json(['data' => $run->fresh()], Response::HTTP_OK);
    }

    public function runsPay(int $payrollRun): JsonResponse
    {
        $run = PayrollRun::withoutGlobalScopes()
            ->where('company_id', TenantContext::getCompanyId())
            ->findOrFail($payrollRun);

        if (! in_array($run->status, ['draft', 'validated'], true)) {
            return response()->json(['message' => 'Cette paie est déjà payée.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $companyId = $run->company_id;

        foreach ($run->payslips as $payslip) {
            $expense = Expense::create([
                'company_id' => $companyId,
                'description' => 'Salaire ' . $payslip->employee?->fullName() . ' — ' . $run->period,
                'category' => 'salaire',
                'amount' => $payslip->net_salary_xof,
                'date' => $run->period_end,
                'created_by' => request()->user()?->id,
                'metadata' => ['payroll_run_id' => $run->id, 'payslip_id' => $payslip->id],
            ]);

            $payslip->update(['status' => 'paid', 'expense_id' => $expense->id]);

            $salaryAccount = AccountingAccount::firstOrCreate(
                ['company_id' => $companyId, 'code' => '641'],
                ['name' => 'Charges de personnel', 'type' => 'expense', 'is_active' => true]
            );
            $bankAccount = AccountingAccount::firstOrCreate(
                ['company_id' => $companyId, 'code' => '521'],
                ['name' => 'Banque', 'type' => 'asset', 'is_active' => true]
            );

            AccountingEntry::create([
                'company_id' => $companyId,
                'account_id' => $salaryAccount->id,
                'entry_type' => 'debit',
                'amount_xof' => $payslip->net_salary_xof,
                'entry_date' => $run->period_end,
                'description' => 'Salaire ' . $payslip->employee?->fullName() . ' — ' . $run->period,
                'reference_type' => Payslip::class,
                'reference_id' => $payslip->id,
            ]);

            AccountingEntry::create([
                'company_id' => $companyId,
                'account_id' => $bankAccount->id,
                'entry_type' => 'credit',
                'amount_xof' => $payslip->net_salary_xof,
                'entry_date' => $run->period_end,
                'description' => 'Paiement salaire ' . $payslip->employee?->fullName() . ' — ' . $run->period,
                'reference_type' => Payslip::class,
                'reference_id' => $payslip->id,
            ]);
        }

        $run->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $run->payslips()->update(['status' => 'paid']);

        return response()->json(['data' => $run->fresh(['payslips.employee'])], Response::HTTP_OK);
    }
}
