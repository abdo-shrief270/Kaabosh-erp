<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Payroll\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage_employees')
            && $user->tenant_id === $employee->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage_employees')
            && $user->tenant_id === $employee->tenant_id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage_employees')
            && $user->tenant_id === $employee->tenant_id;
    }
}
