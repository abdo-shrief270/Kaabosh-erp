<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new tenant with its admin user.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, tenant: Tenant, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $trialEndsAt = now()->addDays(14);

            // Single-company-per-account: one registration = one Company
            // (tenant) + its admin user. No Firm / Model-B wrapper.
            $tenant = Tenant::query()->create([
                'name'    => $data['tenant_name'],
                'slug'    => $data['tenant_slug'],
                'status'  => TenantStatus::Trial,
                'trial_ends_at' => $trialEndsAt,
                'settings' => [
                    'locale' => 'ar',
                    'timezone' => 'Africa/Cairo',
                    'currency' => 'EGP',
                    'fiscal_year_start' => '01-01',
                ],
            ]);

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => UserRole::Admin,
                'locale' => 'ar',
            ]);

            // Assign Spatie role
            try {
                $user->assignRole('admin');
            } catch (\Throwable) { /* role may not exist yet */
            }

            $this->assignFreeTrialSubscription($tenant->id, $trialEndsAt);

            $token = $user->createToken('auth-token')->plainTextToken;

            return compact('user', 'tenant', 'token');
        });
    }

    /**
     * Attach the Free Trial plan to a freshly-created tenant. Skips silently
     * if the plan hasn't been seeded (e.g. on bare local installs) so the
     * registration flow never fails for an environmental reason.
     */
    private function assignFreeTrialSubscription(int $tenantId, \Illuminate\Support\Carbon $trialEndsAt): void
    {
        $plan = Plan::query()->where('slug', 'free_trial')->first();

        if (! $plan) {
            Log::warning('Free trial plan not found; skipping auto-subscription', ['tenant_id' => $tenantId]);

            return;
        }

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'billing_cycle' => 'monthly',
            'price' => '0.00',
            'currency' => $plan->currency ?? 'EGP',
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => $trialEndsAt->copy()->toDateString(),
        ]);
    }

    /**
     * Authenticate a user and return a Sanctum token.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::query()
            ->with('tenant')
            ->withoutGlobalScope('tenant')
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Check tenant accessibility (skip for super admins)
        if ($user->tenant_id && $user->tenant && ! $user->tenant->isAccessible()) {
            throw ValidationException::withMessages([
                'email' => ['Your organization account is currently '.$user->tenant->status->label().'.'],
            ]);
        }

        // Single-company-per-account: any active user with an accessible
        // company may log in. (The muhasebi Model-B firm-attachment gate
        // was removed — kaabosh has no firms.)

        $user->recordLogin();

        $token = $user->createToken('auth-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Revoke the current user's token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
