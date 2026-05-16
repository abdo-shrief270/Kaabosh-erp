<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Auth\Services\PermissionService;
use App\Domain\Shared\Services\FeatureFlagService;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBrokerFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => __('messages.auth.registered'),
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role->value,
                ],
                'tenant' => [
                    'id' => $result['tenant']->id,
                    'name' => $result['tenant']->name,
                    'slug' => $result['tenant']->slug,
                    'status' => $result['tenant']->status->value,
                    'trial_ends_at' => $result['tenant']->trial_ends_at?->toISOString(),
                ],
                'token' => $result['token'],
            ],
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        $user = $result['user'];

        return response()->json([
            'message' => __('messages.auth.logged_in'),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'tenant_id' => $user->tenant_id,
                ],
                'token' => $result['token'],
                // Admins are nudged to enroll in 2FA on first login; the SPA
                // routes them to /settings/two-factor when this flag is true.
                // Once enrolled (`two_factor_enabled = true`), the flag stays
                // false on subsequent logins. Non-admin users always see false.
                'requires_2fa' => $user->isAdmin() && ! $user->two_factor_enabled,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => __('messages.auth.logged_out'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant_id ? Tenant::find($user->tenant_id) : null;

        // Resolve the active subscription's plan so the SPA can apply plan-
        // level gates (manifest `plans: [...]`) from /me alone without a
        // second /subscription round-trip. The dedicated /subscription
        // endpoint still returns richer detail (billing cycle, periods,
        // limits, usage) when the subscription page needs it.
        $activeSub = $tenant ? Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->with('plan:id,slug,name_en,name_ar')
            ->first() : null;

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'locale' => $user->locale,
                'tenant_id' => $user->tenant_id,
                'timezone' => $user->timezone,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'last_login_at' => $user->last_login_at?->toISOString(),
                'permissions' => PermissionService::getUserPermissions($user),
                'spatie_roles' => $user->getRoleNames(),
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'logo_path' => $tenant->logo_path,
                    'favicon_path' => $tenant->favicon_path,
                    'city' => $tenant->city,
                    'features' => $this->tenantFeatures($tenant->id),
                    'plan' => $activeSub?->plan ? [
                        'id' => $activeSub->plan->id,
                        'slug' => $activeSub->plan->slug,
                        'name_en' => $activeSub->plan->name_en,
                        'name_ar' => $activeSub->plan->name_ar,
                    ] : null,
                    'subscription_status' => $activeSub?->status->value,
                ] : null,
            ],
        ]);
    }

    /**
     * Resolve the effective feature-flag map for a tenant.
     *
     * Layering (in order, last wins):
     *   1. Every slug in `config/features.php` catalog starts at false.
     *   2. Active subscription's Plan.features JSON (the plan bundle).
     *   3. Per-tenant overrides from the `feature_flags` admin table.
     *
     * Must stay consistent with `CheckFeature` middleware which applies the
     * same precedence: FeatureFlagService (admin override) → PlanFeatureCache
     * (plan bundle). Returning only the admin-override layer here (as the
     * previous implementation did) produced an empty map whenever the admin
     * hadn't populated `feature_flags` — breaking frontend nav gating even
     * though the backend route-level check passed via the plan fallback.
     *
     * Cached for 5 minutes inside FeatureFlagService so repeated /me calls
     * don't thrash the DB.
     *
     * @return array<string, bool>
     */
    private function tenantFeatures(int $tenantId): array
    {
        // Single source of truth: FeatureFlagService::getAllForTenant resolves
        // firm tier + SuperAdmin firm-level overrides in one place.
        return FeatureFlagService::getAllForTenant($tenantId);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'phone', 'locale', 'timezone']));

        return response()->json([
            'message' => __('messages.success.updated'),
            'data' => ['name' => $user->name, 'phone' => $user->phone, 'locale' => $user->locale, 'timezone' => $user->timezone],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('messages.auth.invalid_credentials')],
            ]);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        return response()->json(['message' => __('messages.success.updated')]);
    }

    /**
     * Send a password-reset link to the supplied email if it belongs to a
     * registered user. We always return the same 200 response — including for
     * unknown emails — so attackers cannot enumerate valid accounts via this
     * endpoint. Real send failures (mailer down) still log; the user still
     * sees the generic success message.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        PasswordBrokerFacade::sendResetLink($request->only('email'));

        return response()->json([
            'message' => __('passwords.sent'),
        ]);
    }

    /**
     * Verify the reset token and rotate the password. Token is consumed by
     * the broker on success and rejected on mismatch/expiry. The new password
     * must satisfy the global Password::defaults() policy.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBrokerFacade::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        // Token invalid / expired / email mismatch — surface as a 422 field
        // error so the SPA's existing validation-handling path renders it
        // inline rather than as a banner.
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
