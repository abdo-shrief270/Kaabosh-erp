<?php

declare(strict_types=1);

namespace App\Policies\SuperAdmin;

use App\Domain\Shared\Models\FeatureFlag;
use App\Models\User;

/**
 * SuperAdmin policy for FeatureFlag management.
 */
class FeatureFlagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->isSuperAdmin();
    }
}
