<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\EmployeeSalaryDetail;
use App\Domain\Payroll\Models\SalaryComponent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SalaryComponentService
{
    // ──────────────────────────────────────
    // Salary Components CRUD
    // ──────────────────────────────────────

    /**
     * List salary components with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return SalaryComponent::query()
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'ilike', "%{$filters['search']}%"))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new salary component.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalaryComponent
    {
        return SalaryComponent::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            ...$data,
        ]);
    }

    /**
     * Update an existing salary component.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SalaryComponent $component, array $data): SalaryComponent
    {
        $component->update($data);

        return $component->refresh();
    }

    /**
     * Delete a salary component.
     *
     * @throws ValidationException
     */
    public function delete(SalaryComponent $component): void
    {
        $hasAssignments = EmployeeSalaryDetail::query()
            ->where('salary_component_id', $component->id)
            ->exists();

        if ($hasAssignments) {
            throw ValidationException::withMessages([
                'component' => [
                    'Cannot delete a component that is assigned to employees.',
                    'لا يمكن حذف مكون مرتب مخصص لموظفين.',
                ],
            ]);
        }

        $component->delete();
    }

    // ──────────────────────────────────────
    // Employee Assignment
    // ──────────────────────────────────────

    /**
     * Assign a salary component to an employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function assignToEmployee(int $employeeId, int $componentId, array $data): EmployeeSalaryDetail
    {
        $exists = EmployeeSalaryDetail::query()
            ->where('employee_id', $employeeId)
            ->where('salary_component_id', $componentId)
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'component' => [
                    'This component is already assigned to this employee.',
                    'هذا المكون مخصص بالفعل لهذا الموظف.',
                ],
            ]);
        }

        return EmployeeSalaryDetail::query()->create([
            'employee_id' => $employeeId,
            'salary_component_id' => $componentId,
            'amount' => $data['amount'],
            'is_active' => $data['is_active'] ?? true,
            'effective_from' => $data['effective_from'] ?? now(),
            'effective_to' => $data['effective_to'] ?? null,
        ]);
    }

    /**
     * Unassign a salary component from an employee.
     */
    public function unassignFromEmployee(EmployeeSalaryDetail $detail): void
    {
        $detail->update([
            'is_active' => false,
            'effective_to' => now(),
        ]);
    }

    /**
     * Get active salary components for an employee.
     */
    public function getEmployeeComponents(int $employeeId): Collection
    {
        return EmployeeSalaryDetail::query()
            ->with('salaryComponent')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            })
            ->get();
    }
}
