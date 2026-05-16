<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\EmployeeLoan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreLoanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $loans = EmployeeLoan::where('tenant_id', app('tenant.id'))
            ->with('employee.user')
            ->when($request->query('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('loan_type'), fn ($q, $type) => $q->where('loan_type', $type))
            ->orderByDesc('created_at')
            ->paginate(min((int) ($request->query('per_page', 15)), 100));

        return $this->success($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = EmployeeLoan::create([
            'tenant_id' => app('tenant.id'),
            'remaining_balance' => $request->validated('amount'),
            'status' => 'active',
            'approved_by' => $request->user()->id,
            ...$request->validated(),
        ]);

        return $this->created($loan->load('employee.user'));
    }

    public function recordInstallment(Request $request, EmployeeLoan $loan): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$loan->remaining_balance],
        ]);

        $amount = (float) $request->validated('amount');

        $loan->remaining_balance = (float) $loan->remaining_balance - $amount;

        if ($loan->remaining_balance <= 0) {
            $loan->remaining_balance = 0;
            $loan->status = 'completed';
        }

        $loan->save();

        return $this->success($loan->fresh(), 'تم تسجيل القسط بنجاح.');
    }

    public function cancel(EmployeeLoan $loan): JsonResponse
    {
        if ($loan->status !== 'active') {
            return response()->json([
                'message' => 'لا يمكن إلغاء قرض غير نشط.',
            ], 422);
        }

        $loan->update(['status' => 'cancelled']);

        return $this->success($loan->fresh(), 'تم إلغاء القرض بنجاح.');
    }
}
