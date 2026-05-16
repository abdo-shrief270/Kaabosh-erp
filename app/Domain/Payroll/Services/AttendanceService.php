<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\AttendanceRecord;
use App\Domain\Payroll\Models\Employee;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    private const STANDARD_HOURS = '8.00';

    /**
     * Create or update an attendance record.
     * Calculates hours_worked from check_in/check_out and overtime if > 8 hours.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): AttendanceRecord
    {
        $hoursWorked = '0.00';
        $overtimeHours = '0.00';

        if (isset($data['check_in'], $data['check_out'])) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);

            $totalMinutes = (string) $checkOut->diffInMinutes($checkIn);
            $hoursWorked = bcdiv($totalMinutes, '60', 2);

            if (bccomp($hoursWorked, self::STANDARD_HOURS, 2) > 0) {
                $overtimeHours = bcsub($hoursWorked, self::STANDARD_HOURS, 2);
            }
        }

        $record = AttendanceRecord::query()->updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'date' => $data['date'],
            ],
            [
                'tenant_id' => (int) app('tenant.id'),
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'hours_worked' => $hoursWorked,
                'overtime_hours' => $overtimeHours,
                'status' => $data['status'] ?? 'present',
                'notes' => $data['notes'] ?? null,
            ]
        );

        return $record->refresh();
    }

    /**
     * Batch import attendance records.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, AttendanceRecord>
     */
    public function bulkRecord(array $records): array
    {
        return DB::transaction(function () use ($records): array {
            $results = [];

            foreach ($records as $data) {
                $results[] = $this->record($data);
            }

            return $results;
        });
    }

    /**
     * List attendance records with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return AttendanceRecord::query()
            ->with('employee.user')
            ->when(isset($filters['employee_id']), fn ($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->where('date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->where('date', '<=', $filters['date_to']))
            ->orderByDesc('date')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Monthly attendance summary for an employee.
     * Returns present days, absent days, late count, and total overtime hours.
     *
     * @return array{present_days: int, absent_days: int, late_days: int, overtime_hours: string, total_hours_worked: string}
     */
    public function summary(int $employeeId, string $month): array
    {
        // Parse month as YYYY-MM
        $date = Carbon::parse($month.'-01');
        $startDate = $date->copy()->startOfMonth()->toDateString();
        $endDate = $date->copy()->endOfMonth()->toDateString();

        $records = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $presentDays = 0;
        $absentDays = 0;
        $lateDays = 0;
        $totalOvertime = '0.00';
        $totalHoursWorked = '0.00';

        foreach ($records as $record) {
            match ($record->status) {
                'present' => $presentDays++,
                'absent' => $absentDays++,
                'late' => $lateDays++,
                default => null,
            };

            // Late employees are still present
            if ($record->status === 'late') {
                $presentDays++;
            }

            $totalOvertime = bcadd($totalOvertime, (string) $record->overtime_hours, 2);
            $totalHoursWorked = bcadd($totalHoursWorked, (string) $record->hours_worked, 2);
        }

        return [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'overtime_hours' => $totalOvertime,
            'total_hours_worked' => $totalHoursWorked,
        ];
    }

    /**
     * Auto-mark all employees without attendance records for a given date as absent.
     */
    public function markAbsent(string $date): int
    {
        $tenantId = (int) app('tenant.id');

        $employeesWithRecords = AttendanceRecord::query()
            ->where('date', $date)
            ->pluck('employee_id');

        $employeesWithoutRecords = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereNotIn('id', $employeesWithRecords)
            ->pluck('id');

        $count = 0;

        foreach ($employeesWithoutRecords as $employeeId) {
            AttendanceRecord::query()->create([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'date' => $date,
                'status' => 'absent',
                'hours_worked' => '0.00',
                'overtime_hours' => '0.00',
            ]);
            $count++;
        }

        return $count;
    }
}
