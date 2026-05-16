<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Enums\PayrollStatus;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\PayrollItem;
use App\Domain\Payroll\Models\PayrollRun;
use App\Domain\Workflow\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly ApprovalWorkflowService $approvals,
    ) {}

    // ──────────────────────────────────────
    // Payroll Runs
    // ──────────────────────────────────────

    /**
     * List payroll runs with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listRuns(array $filters = []): LengthAwarePaginator
    {
        return PayrollRun::query()
            ->when(isset($filters['year']), fn ($q) => $q->where('year', $filters['year']))
            ->when(isset($filters['status']), fn ($q) => $q->ofStatus(PayrollStatus::from($filters['status'])))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a payroll run for a given month.
     *
     * @throws ValidationException
     */
    public function createRun(int $month, int $year): PayrollRun
    {
        $tenantId = (int) app('tenant.id');

        $exists = PayrollRun::query()
            ->forMonth($month, $year)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'month' => [
                    "A payroll run already exists for {$month}/{$year}.",
                    "يوجد مسير رواتب بالفعل لشهر {$month}/{$year}.",
                ],
            ]);
        }

        return PayrollRun::query()->create([
            'tenant_id' => $tenantId,
            'month' => $month,
            'year' => $year,
            'status' => PayrollStatus::Draft,
            'run_by' => Auth::id(),
        ]);
    }

    /**
     * Calculate payroll for all active employees.
     *
     * @throws ValidationException
     */
    public function calculate(PayrollRun $run): PayrollRun
    {
        if (! $run->status->canCalculate()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Payroll can only be calculated from draft status.',
                    'يمكن حساب مسير الرواتب من حالة المسودة فقط.',
                ],
            ]);
        }

        return DB::transaction(function () use ($run): PayrollRun {
            // Delete any existing items (idempotent recalculation)
            $run->items()->delete();

            $employees = Employee::query()
                ->with('user')
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->get();

            $totalGross = '0.00';
            $totalDeductions = '0.00';
            $totalNet = '0.00';
            $totalSI = '0.00';
            $totalTax = '0.00';

            foreach ($employees as $employee) {
                $item = $this->calculateEmployeePayroll($run, $employee);

                $totalGross = bcadd($totalGross, (string) $item->gross_salary, 2);
                $employeeDeductions = bcadd(
                    bcadd((string) $item->social_insurance_employee, (string) $item->income_tax, 2),
                    (string) $item->other_deductions,
                    2
                );
                $totalDeductions = bcadd($totalDeductions, $employeeDeductions, 2);
                $totalNet = bcadd($totalNet, (string) $item->net_salary, 2);
                $totalSI = bcadd($totalSI, bcadd((string) $item->social_insurance_employee, (string) $item->social_insurance_employer, 2), 2);
                $totalTax = bcadd($totalTax, (string) $item->income_tax, 2);
            }

            $run->update([
                'status' => PayrollStatus::Calculated,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'total_social_insurance' => $totalSI,
                'total_tax' => $totalTax,
            ]);

            return $run->refresh()->load('items.employee.user');
        });
    }

    /**
     * Approve a calculated payroll run.
     *
     * @throws ValidationException
     */
    public function approve(PayrollRun $run): PayrollRun
    {
        if (! $run->status->canApprove()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Only calculated payroll runs can be approved.',
                    'يمكن اعتماد مسير الرواتب المحسوب فقط.',
                ],
            ]);
        }

        $run->update([
            'status' => PayrollStatus::Approved,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $run->refresh();
    }

    /**
     * Mark a payroll run as paid.
     *
     * @throws ValidationException
     */
    public function markPaid(PayrollRun $run): PayrollRun
    {
        if (! $run->status->canMarkPaid()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Only approved payroll runs can be marked as paid.',
                    'يمكن تحديد مسير الرواتب المعتمد كمدفوع فقط.',
                ],
            ]);
        }

        // Gate by total_net — the cash that actually moves to employees.
        if (! $this->approvals->isApproved('payroll_run', $run->id, (float) $run->total_net)) {
            throw \App\Domain\Workflow\Exceptions\ApprovalRequiredException::for(
                'payroll_run',
                $run->id,
                'This payroll run requires approval before it can be marked as paid.',
                (float) $run->total_net,
            );
        }

        $run->update(['status' => PayrollStatus::Paid]);

        return $run->refresh();
    }

    /**
     * Delete a draft payroll run.
     *
     * @throws ValidationException
     */
    public function deleteRun(PayrollRun $run): void
    {
        if (! $run->status->canEdit()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Only draft payroll runs can be deleted.',
                    'يمكن حذف مسير الرواتب المسودة فقط.',
                ],
            ]);
        }

        $run->delete();
    }

    /**
     * Show a payroll run with items.
     */
    public function showRun(PayrollRun $run): PayrollRun
    {
        return $run->load('items.employee.user');
    }

    // ──────────────────────────────────────
    // Employees
    // ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listEmployees(array $filters = []): LengthAwarePaginator
    {
        return Employee::query()
            ->with('user')
            ->when(
                isset($filters['search']),
                fn ($q) => $q->whereHas('user', function ($q) use ($filters): void {
                    $q->where('name', 'ilike', "%{$filters['search']}%")
                        ->orWhere('email', 'ilike', "%{$filters['search']}%");
                })
            )
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createEmployee(array $data): Employee
    {
        $tenantId = (int) app('tenant.id');

        $exists = Employee::query()
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => [
                    'An employee record already exists for this user.',
                    'يوجد سجل موظف بالفعل لهذا المستخدم.',
                ],
            ]);
        }

        return Employee::query()->create([
            'tenant_id' => $tenantId,
            ...$data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->refresh();
    }

    public function deleteEmployee(Employee $employee): void
    {
        $employee->delete();
    }

    // ──────────────────────────────────────
    // Private
    // ──────────────────────────────────────

    private function calculateEmployeePayroll(PayrollRun $run, Employee $employee): PayrollItem
    {
        $baseSalary = (string) $employee->base_salary;
        $allowances = '0.00';
        $overtimeHours = '0.00';
        $overtimeAmount = '0.00';
        $otherDeductions = '0.00';

        $grossSalary = bcadd(bcadd($baseSalary, $allowances, 2), $overtimeAmount, 2);

        // Social insurance
        $si = EgyptianTaxService::calculateSocialInsurance($baseSalary, $employee->is_insured);

        // Taxable income: gross - SI employee share - monthly personal exemption
        $monthlyExemption = bcdiv((string) EgyptianTaxService::personalExemption(), '12', 2);
        $taxableMonthly = bcsub(bcsub($grossSalary, (string) $si['employee_share'], 2), $monthlyExemption, 2);
        if (bccomp($taxableMonthly, '0', 2) < 0) {
            $taxableMonthly = '0.00';
        }

        // Annual tax / 12 for monthly
        $annualTaxable = bcmul($taxableMonthly, '12', 2);
        $annualTax = EgyptianTaxService::calculateIncomeTax($annualTaxable);
        $monthlyTax = bcdiv((string) $annualTax, '12', 2);

        $totalDeductions = bcadd(bcadd((string) $si['employee_share'], $monthlyTax, 2), $otherDeductions, 2);
        $netSalary = bcsub($grossSalary, $totalDeductions, 2);

        return PayrollItem::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'gross_salary' => $grossSalary,
            'social_insurance_employee' => $si['employee_share'],
            'social_insurance_employer' => $si['employer_share'],
            'taxable_income' => $taxableMonthly,
            'income_tax' => $monthlyTax,
            'other_deductions' => $otherDeductions,
            'net_salary' => $netSalary,
        ]);
    }
}
