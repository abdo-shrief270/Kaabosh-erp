<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\TimeTracking\Models\TimesheetEntry;
use App\Models\User;

class TimesheetEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_timesheets');
    }

    public function view(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('manage_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_timesheets');
    }

    public function update(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('manage_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }

    public function delete(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('manage_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }

    public function approve(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('approve_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }

    public function reject(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('manage_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }

    public function submit(User $user, TimesheetEntry $timesheetEntry): bool
    {
        return $user->hasPermissionTo('manage_timesheets')
            && $user->tenant_id === $timesheetEntry->tenant_id;
    }
}
