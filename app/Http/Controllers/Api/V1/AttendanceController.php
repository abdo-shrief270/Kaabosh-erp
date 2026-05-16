<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\AttendanceRecord;
use App\Domain\Payroll\Models\Employee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreAttendanceRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = AttendanceRecord::where('tenant_id', app('tenant.id'))
            ->with('employee.user')
            ->when($request->query('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('date_from'), fn ($q, $d) => $q->where('date', '>=', $d))
            ->when($request->query('date_to'), fn ($q, $d) => $q->where('date', '<=', $d))
            ->orderByDesc('date')
            ->paginate(min((int) ($request->query('per_page', 15)), 100));

        return $this->success($records);
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $hoursWorked = 0;
        if (! empty($validated['check_in']) && ! empty($validated['check_out'])) {
            $checkIn = Carbon::createFromFormat('H:i', $validated['check_in']);
            $checkOut = Carbon::createFromFormat('H:i', $validated['check_out']);
            $hoursWorked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        $record = AttendanceRecord::updateOrCreate(
            [
                'tenant_id' => app('tenant.id'),
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
            ],
            [
                'check_in' => $validated['check_in'] ?? null,
                'check_out' => $validated['check_out'] ?? null,
                'hours_worked' => $hoursWorked,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return $this->created($record->load('employee.user'));
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('tenant_id', app('tenant.id')),
            ],
            'records.*.date' => ['required', 'date'],
            'records.*.check_in' => ['nullable', 'date_format:H:i'],
            'records.*.check_out' => ['nullable', 'date_format:H:i'],
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'])],
            'records.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = DB::transaction(function () use ($request) {
            $results = [];

            foreach ($request->validated('records') as $record) {
                $hoursWorked = 0;
                if (! empty($record['check_in']) && ! empty($record['check_out'])) {
                    $checkIn = Carbon::createFromFormat('H:i', $record['check_in']);
                    $checkOut = Carbon::createFromFormat('H:i', $record['check_out']);
                    $hoursWorked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
                }

                $results[] = AttendanceRecord::updateOrCreate(
                    [
                        'tenant_id' => app('tenant.id'),
                        'employee_id' => $record['employee_id'],
                        'date' => $record['date'],
                    ],
                    [
                        'check_in' => $record['check_in'] ?? null,
                        'check_out' => $record['check_out'] ?? null,
                        'hours_worked' => $hoursWorked,
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                    ],
                );
            }

            return $results;
        });

        return $this->created($created, 'تم تسجيل '.count($created).' سجل حضور بنجاح.');
    }

    public function summary(Request $request, Employee $employee): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $month);

        $records = AttendanceRecord::where('tenant_id', app('tenant.id'))
            ->where('employee_id', $employee->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get();

        $summary = [
            'employee_id' => $employee->id,
            'month' => $month,
            'total_days' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
            'holiday' => $records->where('status', 'holiday')->count(),
            'total_hours_worked' => $records->sum('hours_worked'),
            'total_overtime_hours' => $records->sum('overtime_hours'),
        ];

        return $this->success($summary);
    }
}
