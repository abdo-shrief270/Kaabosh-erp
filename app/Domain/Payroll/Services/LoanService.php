<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\EmployeeLoan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class LoanService
{
    /**
     * List loans with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return EmployeeLoan::query()
            ->with('employee.user')
            ->when(isset($filters['employee_id']), fn ($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new loan for an employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmployeeLoan
    {
        return EmployeeLoan::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            'employee_id' => $data['employee_id'],
            'loan_type' => $data['loan_type'],
            'amount' => $data['amount'],
            'remaining_balance' => $data['amount'],
            'monthly_installment' => $data['monthly_installment'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'active',
        ]);
    }

    /**
     * Record an installment payment against a loan.
     *
     * @throws ValidationException
     */
    public function recordInstallment(EmployeeLoan $loan, string $amount): EmployeeLoan
    {
        if ($loan->status !== 'active') {
            throw ValidationException::withMessages([
                'loan' => [
                    'Installments can only be recorded for active loans.',
                    'يمكن تسجيل الأقساط للقروض النشطة فقط.',
                ],
            ]);
        }

        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Installment amount must be greater than zero.',
                    'يجب أن يكون مبلغ القسط أكبر من صفر.',
                ],
            ]);
        }

        $newBalance = bcsub((string) $loan->remaining_balance, $amount, 2);

        if (bccomp($newBalance, '0', 2) < 0) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Installment amount exceeds the remaining balance.',
                    'مبلغ القسط يتجاوز الرصيد المتبقي.',
                ],
            ]);
        }

        $updateData = ['remaining_balance' => $newBalance];

        if (bccomp($newBalance, '0', 2) === 0) {
            $updateData['status'] = 'completed';
        }

        $loan->update($updateData);

        return $loan->refresh();
    }

    /**
     * Cancel an active loan.
     *
     * @throws ValidationException
     */
    public function cancel(EmployeeLoan $loan): EmployeeLoan
    {
        if ($loan->status !== 'active') {
            throw ValidationException::withMessages([
                'loan' => [
                    'Only active loans can be cancelled.',
                    'يمكن إلغاء القروض النشطة فقط.',
                ],
            ]);
        }

        $loan->update(['status' => 'cancelled']);

        return $loan->refresh();
    }

    /**
     * Get active loans for an employee (used for payroll deduction).
     */
    public function getActiveLoans(int $employeeId): Collection
    {
        return EmployeeLoan::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'active')
            ->orderBy('start_date')
            ->get();
    }
}
