<?php

declare(strict_types=1);

use App\Domain\Payroll\Enums\ContractType;
use App\Domain\Payroll\Enums\OvertimeType;
use App\Domain\Payroll\Models\Employee;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);
});

// ──────────────────────────────────────
// Overtime Calculations
// ──────────────────────────────────────

test('overtime weekday: salary 6000, 10 hours = 337.50', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/overtime', [
            'base_salary' => 6000,
            'hours' => 10,
            'overtime_type' => 'weekday',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.amount', '337.50')
        ->assertJsonPath('data.rate', '1.35');
});

test('overtime friday: salary 6000, 8 hours = 400.00', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/overtime', [
            'base_salary' => 6000,
            'hours' => 8,
            'overtime_type' => 'friday',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.amount', '400.00')
        // OvertimeType::rate() returns bcmath-compatible strings, not floats.
        ->assertJsonPath('data.rate', '2.00');
});

// ──────────────────────────────────────
// End of Service
// ──────────────────────────────────────

test('end of service: salary 10000, 7 years = 150000', function (): void {
    // First 5 years: 5 * 2 = 10 months
    // Remaining 2 years: 2 * 2.5 = 5 months
    // Total: 15 months * 10000 = 150,000
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/end-of-service', [
            'monthly_salary' => 10000,
            'years_of_service' => 7,
            'termination_type' => 'termination',
        ]);

    $response->assertOk()
        // Service returns total_months as a plain int (5 + 5) + (5), not a float.
        ->assertJsonPath('data.total_months', 15)
        ->assertJsonPath('data.amount', '150000.00');
});

test('end of service: salary 10000, 3 years = 60000', function (): void {
    // 3 years * 2 months = 6 months * 10000 = 60,000
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/end-of-service', [
            'monthly_salary' => 10000,
            'years_of_service' => 3,
            'termination_type' => 'resignation',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.total_months', 6)
        ->assertJsonPath('data.amount', '60000.00');
});

// ──────────────────────────────────────
// Leave Entitlement
// ──────────────────────────────────────

test('annual leave: 5 years service = 21 days', function (): void {
    $employee = Employee::factory()->create([
        'tenant_id' => $this->tenant->id,
        'hire_date' => now()->subYears(5),
    ]);

    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson("/api/v1/labor-law/leave-entitlement/{$employee->id}");

    $response->assertOk()
        ->assertJsonPath('data.annual_leave_days', 21)
        ->assertJsonPath('data.sick_leave_days', 180)
        ->assertJsonPath('data.maternity_leave_days', 90);
});

test('annual leave: 12 years service = 30 days', function (): void {
    $employee = Employee::factory()->create([
        'tenant_id' => $this->tenant->id,
        'hire_date' => now()->subYears(12),
    ]);

    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson("/api/v1/labor-law/leave-entitlement/{$employee->id}");

    $response->assertOk()
        ->assertJsonPath('data.annual_leave_days', 30);
});

// ──────────────────────────────────────
// Minimum Wage Validation
// ──────────────────────────────────────

test('minimum wage: 7000 is valid', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/validate-wage', [
            'salary' => 7000,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.minimum', '6000.00');
});

test('minimum wage: 5000 is invalid', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/validate-wage', [
            'salary' => 5000,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.valid', false)
        ->assertJsonPath('data.minimum', '6000.00');
});

// ──────────────────────────────────────
// Social Insurance
// ──────────────────────────────────────

test('social insurance: basic 8000, variable 3000 = employee total 1210', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/social-insurance', [
            'basic_salary' => 8000,
            'variable_salary' => 3000,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.employee_basic', '880.00')
        ->assertJsonPath('data.employee_variable', '330.00')
        ->assertJsonPath('data.employee_total', '1210.00');
});

test('social insurance caps: basic 15000 capped at 12600', function (): void {
    $response = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/labor-law/social-insurance', [
            'basic_salary' => 15000,
            'variable_salary' => 0,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.basic_salary', '12600.00')
        ->assertJsonPath('data.employee_basic', '1386.00');
});

// ──────────────────────────────────────
// Enum Labels
// ──────────────────────────────────────

test('ContractType labels are all non-empty', function (): void {
    foreach (ContractType::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->labelAr())->toBeString()->not->toBeEmpty();
    }
});

test('OvertimeType rates: weekday=1.35, friday=2.0', function (): void {
    // rate() returns bcmath-compatible strings so payroll calcs don't lose
    // precision in float arithmetic.
    expect(OvertimeType::Weekday->rate())->toBe('1.35');
    expect(OvertimeType::Friday->rate())->toBe('2.00');
});
