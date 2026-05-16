<?php

declare(strict_types=1);

use App\Domain\Payroll\Enums\AttendanceStatus;
use App\Domain\Payroll\Enums\CalculationType;
use App\Domain\Payroll\Enums\LeaveStatus;
use App\Domain\Payroll\Enums\LoanStatus;
use App\Domain\Payroll\Enums\SalaryComponentType;
use App\Domain\Payroll\Models\AttendanceRecord;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\EmployeeLoan;
use App\Domain\Payroll\Models\EmployeeSalaryDetail;
use App\Domain\Payroll\Models\LeaveBalance;
use App\Domain\Payroll\Models\LeaveType;

// ──────────────────────────────────────
// Enum Labels
// ──────────────────────────────────────

test('SalaryComponentType labels are all non-empty', function (): void {
    foreach (SalaryComponentType::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->labelAr())->toBeString()->not->toBeEmpty();
    }
});

test('CalculationType labels are all non-empty', function (): void {
    foreach (CalculationType::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->labelAr())->toBeString()->not->toBeEmpty();
    }
});

test('LoanStatus labels are all non-empty', function (): void {
    foreach (LoanStatus::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->labelAr())->toBeString()->not->toBeEmpty();
    }
});

test('LeaveStatus transitions are correct', function (): void {
    // canApprove only from Pending
    expect(LeaveStatus::Pending->canApprove())->toBeTrue();
    expect(LeaveStatus::Approved->canApprove())->toBeFalse();
    expect(LeaveStatus::Rejected->canApprove())->toBeFalse();
    expect(LeaveStatus::Cancelled->canApprove())->toBeFalse();

    // canReject only from Pending
    expect(LeaveStatus::Pending->canReject())->toBeTrue();
    expect(LeaveStatus::Approved->canReject())->toBeFalse();
    expect(LeaveStatus::Rejected->canReject())->toBeFalse();

    // canCancel from Pending or Approved
    expect(LeaveStatus::Pending->canCancel())->toBeTrue();
    expect(LeaveStatus::Approved->canCancel())->toBeTrue();
    expect(LeaveStatus::Rejected->canCancel())->toBeFalse();
    expect(LeaveStatus::Cancelled->canCancel())->toBeFalse();
});

test('AttendanceStatus labels are all non-empty', function (): void {
    foreach (AttendanceStatus::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->labelAr())->toBeString()->not->toBeEmpty();
    }
});

// ──────────────────────────────────────
// EmployeeSalaryDetail.effectiveAmount
// ──────────────────────────────────────

test('effectiveAmount returns fixed amount correctly', function (): void {
    $detail = new EmployeeSalaryDetail([
        'calculation_type' => CalculationType::Fixed,
        'amount' => 500,
        'percentage' => 0,
    ]);

    expect($detail->effectiveAmount())->toBe('500.00');
});

test('effectiveAmount returns percentage of basic correctly', function (): void {
    $detail = new EmployeeSalaryDetail([
        'calculation_type' => CalculationType::PercentageOfBasic,
        'amount' => 0,
        'percentage' => 10,
    ]);

    expect($detail->effectiveAmount(basicSalary: '5000'))->toBe('500.00');
});

test('effectiveAmount returns percentage of gross correctly', function (): void {
    $detail = new EmployeeSalaryDetail([
        'calculation_type' => CalculationType::PercentageOfGross,
        'amount' => 0,
        'percentage' => 5,
    ]);

    expect($detail->effectiveAmount(grossSalary: '8000'))->toBe('400.00');
});

// ──────────────────────────────────────
// Loan Installments
// ──────────────────────────────────────

test('loan installment reduces remaining balance', function (): void {
    $tenant = createTenant();

    $loan = EmployeeLoan::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 10000,
        'installment_amount' => 2000,
        'remaining_balance' => 10000,
    ]);

    $loan->recordInstallment();

    expect($loan->remaining_balance)->toBe('8000.00');
    expect($loan->status)->toBe(LoanStatus::Active);
});

test('loan completes after all installments', function (): void {
    $tenant = createTenant();

    $loan = EmployeeLoan::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 10000,
        'installment_amount' => 2000,
        'remaining_balance' => 10000,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $loan->recordInstallment();
    }

    expect($loan->remaining_balance)->toBe('0.00');
    expect($loan->status)->toBe(LoanStatus::Completed);
});

// ──────────────────────────────────────
// Leave Balance
// ──────────────────────────────────────

test('leave balance calculates available days correctly', function (): void {
    $balance = new LeaveBalance([
        'entitled_days' => 21,
        'used_days' => 5,
        'carried_days' => 3,
    ]);

    // available = (entitled + carried) - used = (21 + 3) - 5 = 19
    expect($balance->availableDays())->toBe(19);
});

test('leave request approval deducts from balance', function (): void {
    $tenant = createTenant();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $balance = LeaveBalance::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 21,
        'used_days' => 0,
        'carried_days' => 0,
    ]);

    // Simulate approving a 5-day leave request
    $balance->deductDays(5);

    $balance->refresh();
    expect($balance->used_days)->toBe(5);
    expect($balance->availableDays())->toBe(16);
});

// ──────────────────────────────────────
// Attendance
// ──────────────────────────────────────

test('attendance calculates hours worked and overtime', function (): void {
    $record = new AttendanceRecord([
        'date' => '2026-04-07',
        'check_in' => '09:00',
        'check_out' => '18:00',
        'status' => AttendanceStatus::Present,
    ]);

    $record->calculateHours();

    expect($record->hours_worked)->toBe('9.00');
    expect($record->overtime_hours)->toBe('1.00');
});

test('attendance summary counts statuses correctly', function (): void {
    $tenant = createTenant();
    $employee = Employee::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // Create 20 present records
    for ($d = 1; $d <= 20; $d++) {
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => sprintf('2026-03-%02d', $d),
            'check_in' => '09:00',
            'check_out' => '17:00',
            'hours_worked' => 8,
            'overtime_hours' => 0,
            'status' => AttendanceStatus::Present,
        ]);
    }

    // Create 2 absent records
    for ($d = 21; $d <= 22; $d++) {
        AttendanceRecord::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => sprintf('2026-03-%02d', $d),
            'status' => AttendanceStatus::Absent,
        ]);
    }

    // Create 1 late record
    AttendanceRecord::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'date' => '2026-03-23',
        'check_in' => '09:30',
        'check_out' => '17:00',
        'hours_worked' => 7.5,
        'overtime_hours' => 0,
        'status' => AttendanceStatus::Late,
    ]);

    $summary = AttendanceRecord::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->whereBetween('date', ['2026-03-01', '2026-03-31'])
        ->selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status');

    expect((int) $summary[AttendanceStatus::Present->value])->toBe(20);
    expect((int) $summary[AttendanceStatus::Absent->value])->toBe(2);
    expect((int) $summary[AttendanceStatus::Late->value])->toBe(1);
});
