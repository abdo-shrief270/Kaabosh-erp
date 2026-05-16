<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Firm\Models\Firm;
use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

            // Every new registration creates a Firm wrapping a firm-books
            // tenant. The user becomes that firm's owner. From here they can
            // add client-tenants from the firm panel.
            $firm = Firm::query()->create([
                'name'              => $data['tenant_name'],
                'slug'              => $this->uniqueFirmSlug($data['tenant_slug']),
                'email'             => $data['email'] ?? null,
                'phone'             => $data['phone'] ?? null,
                'status'            => 'active',
                'subscription_tier' => FirmSubscriptionTier::Starter->value,
                'settings'          => [],
            ]);

            $tenant = Tenant::query()->create([
                'firm_id' => $firm->id,
                'type'    => TenantType::FirmBooks->value,
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
                'firm_id'   => $firm->id,
                'firm_role' => FirmRole::Owner->value,
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

            $this->assignFirmFreeTrialSubscription($firm->id, $trialEndsAt);

            $token = $user->createToken('auth-token')->plainTextToken;

            return compact('user', 'tenant', 'token');
        });
    }

    /**
     * Attach the Firm Free Trial plan to a freshly-created firm. Subscription
     * row carries firm_id (not tenant_id) — Model B has firm-scoped
     * subscriptions, not per-tenant. The legacy per-tenant subscription path
     * is deprecated.
     */
    private function assignFirmFreeTrialSubscription(int $firmId, \Illuminate\Support\Carbon $trialEndsAt): void
    {
        $plan = Plan::query()->where('slug', 'firm_free_trial')->first();

        if (! $plan) {
            Log::warning('Firm free-trial plan not found; skipping auto-subscription', ['firm_id' => $firmId]);
            return;
        }

        Subscription::query()->create([
            'firm_id'              => $firmId,
            'tenant_id'            => null,
            'plan_id'              => $plan->id,
            'status'               => SubscriptionStatus::Trial,
            'billing_cycle'        => 'monthly',
            'price'                => '0.00',
            'currency'             => $plan->currency ?? 'EGP',
            'trial_ends_at'        => $trialEndsAt,
            'current_period_start' => now()->toDateString(),
            'current_period_end'   => $trialEndsAt->copy()->toDateString(),
        ]);
    }

    private function uniqueFirmSlug(string $base): string
    {
        $slug = $base ?: 'firm-'.Str::random(8);
        $i = 0;
        while (Firm::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base.'-'.$i;
        }
        return $slug;
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

        // Under Model B, only firm staff or client-portal users may log in.
        // Legacy tenant-users (no firm_id, no portal role) were valid in
        // Model A but are no longer allowed — invite them as firm staff
        // or as portal clients explicitly.
        if (! $user->isSuperAdmin()
            && ! $user->firm_id
            && $user->role !== \App\Domain\Shared\Enums\UserRole::Client
        ) {
            throw ValidationException::withMessages([
                'email' => ['This account is not attached to an accounting firm. Contact your firm administrator to be invited.'],
            ]);
        }

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
