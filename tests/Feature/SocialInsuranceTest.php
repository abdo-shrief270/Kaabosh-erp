<?php

declare(strict_types=1);

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\EmployeeInsuranceRecord;
use App\Domain\Payroll\Models\SocialInsuranceRate;
use App\Domain\Payroll\Services\SocialInsuranceService;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    $this->service = app(SocialInsuranceService::class);
});

// ──────────────────────────────────────
// Service: Rate calculation with caps
// ──────────────────────────────────────

describe('Rate calculation with caps', function (): void {

    it('calculates employee and employer shares for basic and variable within caps', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '8000.00',
            'variable_insurance_salary' => '3000.00',
            'is_active' => true,
        ]);

        $result = $this->service->calculate($employee->id, '2025-06');

        // Employee: 8000 * 0.11 = 880, 3000 * 0.11 = 330
        expect($result['employee_basic'])->toBe('880.00');
        expect($result['employee_variable'])->toBe('330.00');
        expect($result['employee_total'])->toBe('1210.00');

        // Employer: 8000 * 0.1875 = 1500, 3000 * 0.1875 = 562.50
        expect($result['employer_basic'])->toBe('1500.00');
        expect($result['employer_variable'])->toBe('562.50');
        expect($result['employer_total'])->toBe('2062.50');
    });

    it('caps basic salary at maximum when exceeded', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '20000.00', // exceeds 12600 cap
            'variable_insurance_salary' => '15000.00', // exceeds 10500 cap
            'is_active' => true,
        ]);

        $result = $this->service->calculate($employee->id, '2025-06');

        // Should use capped values: basic 12600, variable 10500
        expect($result['basic_insurance_salary'])->toBe('12600.00');
        expect($result['variable_insurance_salary'])->toBe('10500.00');

        // Employee: 12600 * 0.11 = 1386, 10500 * 0.11 = 1155
        expect($result['employee_basic'])->toBe('1386.00');
        expect($result['employee_variable'])->toBe('1155.00');
        expect($result['employee_total'])->toBe('2541.00');

        // Employer: 12600 * 0.1875 = 2362.50, 10500 * 0.1875 = 1968.75
        expect($result['employer_basic'])->toBe('2362.50');
        expect($result['employer_variable'])->toBe('1968.75');
        expect($result['employer_total'])->toBe('4331.25');
    });
});

// ──────────────────────────────────────
// Service: Monthly report totals
// ──────────────────────────────────────

describe('Monthly report totals', function (): void {

    it('generates correct totals for multiple employees', function (): void {
        $emp1 = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);
        $emp2 = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $emp1->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $emp2->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '7000.00',
            'variable_insurance_salary' => '3000.00',
            'is_active' => true,
        ]);

        $report = $this->service->monthlyReport('2025-06');

        expect($report['employees'])->toHaveCount(2);

        // Emp1 employee share: (5000*0.11) + (2000*0.11) = 550 + 220 = 770
        // Emp2 employee share: (7000*0.11) + (3000*0.11) = 770 + 330 = 1100
        // Total employee share: 1870
        expect($report['totals']['employee_share'])->toBe('1870.00');

        // Emp1 employer share: (5000*0.1875) + (2000*0.1875) = 937.50 + 375 = 1312.50
        // Emp2 employer share: (7000*0.1875) + (3000*0.1875) = 1312.50 + 562.50 = 1875
        // Total employer share: 3187.50
        expect($report['totals']['employer_share'])->toBe('3187.50');

        // Grand total: 1870 + 3187.50 = 5057.50
        expect($report['totals']['total'])->toBe('5057.50');
    });

    it('excludes exempted employees from monthly report', function (): void {
        $emp = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $emp->id,
            'insurance_type' => 'exempted',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        $report = $this->service->monthlyReport('2025-06');

        expect($report['employees'])->toHaveCount(0);
        expect($report['totals']['total'])->toBe('0.00');
    });
});

// ──────────────────────────────────────
// Service: Exemption check
// ──────────────────────────────────────

describe('Exemption check', function (): void {

    it('identifies exempted insurance type', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'exempted',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        $result = $this->service->exemptionCheck($employee->id);

        expect($result['is_exempt'])->toBeTrue();
        expect($result['reasons'])->toContain('Employee is marked as exempted');
    });

    it('identifies foreigner exemption', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'foreigner',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        $result = $this->service->exemptionCheck($employee->id);

        expect($result['is_exempt'])->toBeTrue();
        expect($result['reasons'])->toContain('Foreign employee — subject to bilateral agreements');
    });

    it('identifies uninsured employee', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => false,
        ]);

        $result = $this->service->exemptionCheck($employee->id);

        expect($result['is_exempt'])->toBeTrue();
        expect($result['reasons'])->toContain('Employee is not registered for insurance');
    });

    it('returns non-exempt for regular insured employee', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        $result = $this->service->exemptionCheck($employee->id);

        expect($result['is_exempt'])->toBeFalse();
        expect($result['reasons'])->toBeEmpty();
    });
});

// ──────────────────────────────────────
// Seeder: 2025 rates
// ──────────────────────────────────────

describe('2025 rates seeded correctly', function (): void {

    it('seeds default 2025 rates via getRates', function (): void {
        $rates = $this->service->getRates(2025);

        expect($rates['basic_employee_rate'])->toBe('0.1100');
        expect($rates['basic_employer_rate'])->toBe('0.1875');
        expect($rates['variable_employee_rate'])->toBe('0.1100');
        expect($rates['variable_employer_rate'])->toBe('0.1875');
        expect($rates['basic_max_salary'])->toBe('12600.00');
        expect($rates['variable_max_salary'])->toBe('10500.00');
    });

    it('persists seeded rates in database', function (): void {
        $this->service->getRates(2025);

        $rate = SocialInsuranceRate::where('year', 2025)->first();

        expect($rate)->not->toBeNull();
        expect($rate->basic_employee_rate)->toBe('0.1100');
        expect($rate->basic_max_salary)->toBe('12600.00');
        expect($rate->variable_max_salary)->toBe('10500.00');
    });
});

// ──────────────────────────────────────
// API: Controller endpoints
// ──────────────────────────────────────

describe('Social Insurance API', function (): void {

    it('calculates insurance via API', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '8000.00',
            'variable_insurance_salary' => '3000.00',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/social-insurance/calculate', [
                'employee_id' => $employee->id,
                'month' => '2025-06',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.employee_total', '1210.00')
            ->assertJsonPath('data.employer_total', '2062.50');
    });

    it('registers employee insurance via API', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/social-insurance/register', [
                'employee_id' => $employee->id,
                'insurance_number' => '12345678',
                'basic_insurance_salary' => 8000,
                'variable_insurance_salary' => 3000,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('employee_insurance_records', [
            'employee_id' => $employee->id,
            'insurance_number' => '12345678',
        ]);
    });

    it('retrieves rates via API', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/social-insurance/rates?year=2025');

        $response->assertOk()
            ->assertJsonPath('data.basic_employee_rate', '0.1100')
            ->assertJsonPath('data.basic_employer_rate', '0.1875');
    });

    it('generates monthly report via API', function (): void {
        $employee = Employee::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_insured' => true,
        ]);

        EmployeeInsuranceRecord::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'insurance_type' => 'regular',
            'basic_insurance_salary' => '5000.00',
            'variable_insurance_salary' => '2000.00',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/social-insurance/monthly-report?month=2025-06');

        $response->assertOk()
            ->assertJsonPath('data.totals.employee_share', '770.00')
            ->assertJsonPath('data.totals.employer_share', '1312.50')
            ->assertJsonPath('data.totals.total', '2082.50');
    });
});
