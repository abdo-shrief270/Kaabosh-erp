<?php

declare(strict_types=1);

namespace App\Policies\SuperAdmin;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

/**
 * SuperAdmin policy for Tenant management.
 *
 * Note: `Gate::before` in AppServiceProvider grants SuperAdmin a blanket bypass,
 * so these methods act as a defensive second layer only.
 */
class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }
}
