<?php

declare(strict_types=1);
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(
    TestCase::class,
    RefreshDatabase::class,
)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeAccessibleTenant', function () {
    return $this->toBeInstanceOf(Tenant::class)
        ->and($this->value->isAccessible())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createTenant(array $attributes = []): Tenant
{
    $tenant = Tenant::factory()->create($attributes);

    // Bind tenant into the container so the BelongsToTenant trait
    // auto-populates tenant_id on models created via factories in tests
    // that don't go through HTTP middleware.
    app()->instance('tenant.id', $tenant->id);
    app()->instance('tenant', $tenant);

    // Every new tenant gets a trial subscription tied to an all-features
    // plan so tenant-scoped routes pass both CheckSubscription and
    // CheckFeature middleware in tests.
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => allFeaturesTestPlan()->id,
    ]);

    return $tenant;
}

function allFeaturesTestPlan(): Plan
{
    // Cache a single "test" plan that enables every feature flag the app
    // gates on. Reused across tenants within the same test.
    static $plan = null;
    if ($plan !== null && Plan::whereKey($plan->id)->exists()) {
        return $plan;
    }

    $features = [
        'accounting', 'clients', 'invoicing', 'bills_vendors', 'expenses',
        'fixed_assets', 'tax', 'inventory', 'payroll', 'timesheets',
        'cost_centers', 'budgeting', 'documents', 'reports', 'audit_log',
        'collections', 'client_portal', 'e_invoice', 'custom_reports',
        'api_access', 'priority_support', 'task_board',
    ];

    $plan = Plan::factory()->create([
        'slug' => 'test-all-features',
        'features' => array_fill_keys($features, true),
        'limits' => [
            'max_users' => -1,
            'max_clients' => -1,
            'max_storage_bytes' => -1,
            'max_invoices_per_month' => -1,
        ],
    ]);

    return $plan;
}

function createUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function createAdminUser(?Tenant $tenant = null): User
{
    return User::factory()->admin()->create([
        'tenant_id' => $tenant?->id ?? createTenant()->id,
    ]);
}

function createSuperAdmin(): User
{
    return User::factory()->superAdmin()->create();
}

function actingAsUser(User $user): User
{
    test()->actingAs($user, 'sanctum');

    return $user;
}
