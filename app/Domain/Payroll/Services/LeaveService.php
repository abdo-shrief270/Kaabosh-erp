<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\LeaveBalance;
use App\Domain\Payroll\Models\LeaveRequest;
use App\Domain\Payroll\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    // ──────────────────────────────────────
    // Leave Types
    // ──────────────────────────────────────

    /**
     * List all leave types.
     */
    public function listTypes(): Collection
    {
        return LeaveType::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new leave type.
     *
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data): LeaveType
    {
        return LeaveType::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            ...$data,
        ]);
    }

    /**
     * Update an existing leave type.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateType(LeaveType $type, array $data): LeaveType
    {
        $type->update($data);

        return $type->refresh();
    }

    /**
     * Delete a leave type.
     */
    public function deleteType(LeaveType $type): void
    {
        $type->delete();
    }

    // ──────────────────────────────────────
    // Leave Balances
    // ──────────────────────────────────────

    /**
     * Initialize leave balances for an employee for a given year.
     * Creates a balance record for each active leave type.
     */
    public function initializeBalances(int $employeeId, int $year): Collection
    {
        $types = LeaveType::query()
            ->where('is_active', true)
            ->get();

        $balances = collect();

        foreach ($types as $type) {
            $exists = LeaveBalance::query()
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $type->id)
                ->where('year', $year)
                ->exists();

            if (! $exists) {
                $balances->push(
                    LeaveBalance::query()->create([
                        'employee_id' => $employeeId,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                        'total_days' => $type->default_days,
                        'used_days' => '0.00',
                        'remaining_days' => $type->default_days,
                    ])
                );
            }
        }

        return $balances;
    }

    /**
     * Get all leave balances for an employee in a given year.
     */
    public function getBalance(int $employeeId, int $year): Collection
    {
        return LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();
    }

    /**
     * Carry forward unused leave days from one year to the next.
     * Respects the max_carry_forward limit on each leave type.
     */
    public function carryForward(int $employeeId, int $fromYear, int $toYear): Collection
    {
        $balances = LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $fromYear)
            ->get();

        $carried = collect();

        foreach ($balances as $balance) {
            $maxCarry = (string) ($balance->leaveType->max_carry_forward ?? '0.00');

            if (bccomp($maxCarry, '0', 2) <= 0) {
                continue;
            }

            $remaining = (string) $balance->remaining_days;
            $carryDays = bccomp($remaining, $maxCarry, 2) <= 0 ? $remaining : $maxCarry;

            if (bccomp($carryDays, '0', 2) <= 0) {
                continue;
            }

            $targetBalance = LeaveBalance::query()
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $balance->leave_type_id)
                ->where('year', $toYear)
                ->first();

            if ($targetBalance) {
                $newTotal = bcadd((string) $targetBalance->total_days, $carryDays, 2);
                $newRemaining = bcadd((string) $targetBalance->remaining_days, $carryDays, 2);
                $targetBalance->update([
                    'total_days' => $newTotal,
                    'remaining_days' => $newRemaining,
                ]);
                $carried->push($targetBalance->refresh());
            }
        }

        return $carried;
    }

    // ──────────────────────────────────────
    // Leave Requests
    // ──────────────────────────────────────

    /**
     * Create a leave request after validating available days.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function request(array $data): LeaveRequest
    {
        $year = (int) date('Y', strtotime($data['start_date']));
        $days = $data['days'];

        $balance = LeaveBalance::query()
            ->where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $year)
            ->first();

        if (! $balance) {
            throw ValidationException::withMessages([
                'leave_type_id' => [
                    'No leave balance found for this type and year. Please initialize balances first.',
                    'لم يتم العثور على رصيد إجازات لهذا النوع والسنة. يرجى تهيئة الأرصدة أولاً.',
                ],
            ]);
        }

        if (bccomp((string) $balance->remaining_days, (string) $days, 2) < 0) {
            throw ValidationException::withMessages([
                'days' => [
                    'Insufficient leave balance. Available: '.$balance->remaining_days.' days.',
                    'رصيد الإجازات غير كافٍ. المتاح: '.$balance->remaining_days.' يوم.',
                ],
            ]);
        }

        return LeaveRequest::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve a leave request and deduct from balance.
     *
     * @throws ValidationException
     */
    public function approve(LeaveRequest $req): LeaveRequest
    {
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => [
                    'Only pending requests can be approved.',
                    'يمكن الموافقة على الطلبات المعلقة فقط.',
                ],
            ]);
        }

        return DB::transaction(function () use ($req): LeaveRequest {
            $year = (int) $req->start_date->format('Y');

            $balance = LeaveBalance::query()
                ->where('employee_id', $req->employee_id)
                ->where('leave_type_id', $req->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $newUsed = bcadd((string) $balance->used_days, (string) $req->days, 2);
            $newRemaining = bcsub((string) $balance->remaining_days, (string) $req->days, 2);

            if (bccomp($newRemaining, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'days' => [
                        'Insufficient leave balance to approve this request.',
                        'رصيد الإجازات غير كافٍ للموافقة على هذا الطلب.',
                    ],
                ]);
            }

            $balance->update([
                'used_days' => $newUsed,
                'remaining_days' => $newRemaining,
            ]);

            $req->update(['status' => 'approved']);

            return $req->refresh();
        });
    }

    /**
     * Reject a leave request with a reason.
     *
     * @throws ValidationException
     */
    public function reject(LeaveRequest $req, string $reason): LeaveRequest
    {
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => [
                    'Only pending requests can be rejected.',
                    'يمكن رفض الطلبات المعلقة فقط.',
                ],
            ]);
        }

        $req->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $req->refresh();
    }

    /**
     * Cancel a leave request. If it was approved, restore the balance.
     */
    public function cancel(LeaveRequest $req): LeaveRequest
    {
        return DB::transaction(function () use ($req): LeaveRequest {
            if ($req->status === 'approved') {
                $year = (int) $req->start_date->format('Y');

                $balance = LeaveBalance::query()
                    ->where('employee_id', $req->employee_id)
                    ->where('leave_type_id', $req->leave_type_id)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                $newUsed = bcsub((string) $balance->used_days, (string) $req->days, 2);
                $newRemaining = bcadd((string) $balance->remaining_days, (string) $req->days, 2);

                $balance->update([
                    'used_days' => $newUsed,
                    'remaining_days' => $newRemaining,
                ]);
            }

            $req->update(['status' => 'cancelled']);

            return $req->refresh();
        });
    }
}
