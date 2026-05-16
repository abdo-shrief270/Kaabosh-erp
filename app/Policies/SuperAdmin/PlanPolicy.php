<?php

declare(strict_types=1);

namespace App\Policies\SuperAdmin;

use App\Domain\Subscription\Models\Plan;
use App\Models\User;

/**
 * SuperAdmin policy for subscription Plan management.
 */
class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }
}
