<?php

declare(strict_types=1);

namespace App\Policies\SuperAdmin;

use App\Domain\Subscription\Models\Subscription;
use App\Models\User;

/**
 * SuperAdmin policy for Subscription management.
 */
class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }
}
