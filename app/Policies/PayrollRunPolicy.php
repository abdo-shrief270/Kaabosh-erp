<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Payroll\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_payroll');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermissionTo('manage_payroll')
            && $user->tenant_id === $payrollRun->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_payroll');
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermissionTo('manage_payroll')
            && $user->tenant_id === $payrollRun->tenant_id;
    }

    public function calculate(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermissionTo('manage_payroll')
            && $user->tenant_id === $payrollRun->tenant_id;
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermissionTo('manage_payroll')
            && $user->tenant_id === $payrollRun->tenant_id;
    }

    public function markPaid(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermissionTo('manage_payroll')
            && $user->tenant_id === $payrollRun->tenant_id;
    }
}
