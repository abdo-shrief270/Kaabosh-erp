<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\LeaveRequest;
use App\Domain\Payroll\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreLeaveRequestRequest;
use App\Http\Requests\Payroll\StoreLeaveTypeRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    // ──────────────────────────────────────
    // Leave Types
    // ──────────────────────────────────────

    public function types(Request $request): JsonResponse
    {
        $types = LeaveType::where('tenant_id', app('tenant.id'))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name_ar')
            ->get();

        return $this->success($types);
    }

    public function createType(StoreLeaveTypeRequest $request): JsonResponse
    {
        $type = LeaveType::create([
            'tenant_id' => app('tenant.id'),
            ...$request->validated(),
        ]);

        return $this->created($type);
    }

    // ──────────────────────────────────────
    // Leave Requests
    // ──────────────────────────────────────

    public function requests(Request $request): JsonResponse
    {
        $leaveRequests = LeaveRequest::where('tenant_id', app('tenant.id'))
            ->with(['employee.user', 'leaveType'])
            ->when($request->query('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('leave_type_id'), fn ($q, $id) => $q->where('leave_type_id', $id))
            ->orderByDesc('created_at')
            ->paginate(min((int) ($request->query('per_page', 15)), 100));

        return $this->success($leaveRequests);
    }

    public function request(StoreLeaveRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        $employee = Employee::where('tenant_id', app('tenant.id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $leaveRequest = LeaveRequest::create([
            'tenant_id' => app('tenant.id'),
            'employee_id' => $employee->id,
            'days' => $days,
            'status' => 'pending',
            ...$validated,
        ]);

        return $this->created($leaveRequest->load('leaveType'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن الموافقة على طلب غير معلق.',
            ], 422);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return $this->success($leaveRequest->fresh()->load(['employee.user', 'leaveType']), 'تمت الموافقة على طلب الإجازة.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن رفض طلب غير معلق.',
            ], 422);
        }

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return $this->success($leaveRequest->fresh()->load(['employee.user', 'leaveType']), 'تم رفض طلب الإجازة.');
    }

    public function cancel(LeaveRequest $leaveRequest): JsonResponse
    {
        if (! in_array($leaveRequest->status, ['pending', 'approved'])) {
            return response()->json([
                'message' => 'لا يمكن إلغاء هذا الطلب.',
            ], 422);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return $this->success($leaveRequest->fresh(), 'تم إلغاء طلب الإجازة.');
    }

    public function balance(Employee $employee): JsonResponse
    {
        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', now()->year)
            ->get()
            ->map(fn ($balance) => [
                'leave_type' => $balance->leaveType,
                'entitled_days' => $balance->entitled_days,
                'used_days' => $balance->used_days,
                'carried_forward' => $balance->carried_forward,
                'remaining' => (float) $balance->entitled_days + (float) $balance->carried_forward - (float) $balance->used_days,
            ]);

        return $this->success($balances);
    }
}
