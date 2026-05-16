<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class ImpersonationService
{
    /**
     * Mint a 1-hour Sanctum API token on behalf of a target tenant user.
     *
     * @throws AuthorizationException
     */
    public function impersonateUser(User $target, string $reason): string
    {
        $admin = auth()->user();

        if (! $admin instanceof User || ! $admin->isSuperAdmin()) {
            throw new AuthorizationException('Only SuperAdmins may impersonate users.');
        }

        if ($target->isSuperAdmin()) {
            throw new AuthorizationException('Refusing to impersonate another SuperAdmin.');
        }

        $tokenName = "impersonation-{$admin->id}-{$target->id}";

        $token = $target->createToken(
            $tokenName,
            ['*'],
            now()->addHour(),
        );

        activity('impersonation')
            ->causedBy($admin)
            ->performedOn($target)
            ->withProperties([
                'reason' => $reason,
                'tenant_id' => $target->tenant_id,
                'ip' => request()->ip(),
            ])
            ->log('user_impersonated');

        Log::channel('stack')->warning('SuperAdmin impersonation', [
            'super_admin_id' => $admin->id,
            'super_admin_email' => $admin->email,
            'target_user_id' => $target->id,
            'target_user_email' => $target->email,
            'tenant_id' => $target->tenant_id,
            'reason' => $reason,
            'token_name' => $tokenName,
            'ip' => request()->ip(),
        ]);

        return $token->plainTextToken;
    }
}
