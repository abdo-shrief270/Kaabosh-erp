<?php

declare(strict_types=1);

namespace App\Policies\SuperAdmin;

use App\Models\User;

/**
 * SuperAdmin policy for User management.
 *
 * SuperAdmin can manage all users across all tenants.
 * A SuperAdmin may not delete/force-delete themselves to avoid lockout.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $user->id !== $target->id;
    }

    public function restore(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $user->id !== $target->id;
    }
}
