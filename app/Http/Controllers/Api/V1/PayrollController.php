<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\PayrollRun;
use App\Domain\Payroll\Services\PayrollService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreEmployeeRequest;
use App\Http\Requests\Payroll\StorePayrollRunRequest;
use App\Http\Requests\Payroll\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\PayrollItemResource;
use App\Http\Resources\PayrollRunResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService,
    ) {}

    // ──────────────────────────────────────
    // Employees
    // ──────────────────────────────────────

    public function listEmployees(Request $request): AnonymousResourceCollection
    {
        return EmployeeResource::collection(
            $this->payrollService->listEmployees([
                'search' => $request->query('search'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function storeEmployee(StoreEmployeeRequest $request): EmployeeResource
    {
        return new EmployeeResource(
            $this->payrollService->createEmployee($request->validated())->load('user'),
        );
    }

    public function showEmployee(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load('user'));
    }

    public function updateEmployee(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        return new EmployeeResource(
            $this->payrollService->updateEmployee($employee, $request->validated())->load('user'),
        );
    }

    public function destroyEmployee(Employee $employee): JsonResponse
    {
        $this->payrollService->deleteEmployee($employee);

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    // ──────────────────────────────────────
    // Payroll Runs
    // ──────────────────────────────────────

    public function index(Request $request): AnonymousResourceCollection
    {
        return PayrollRunResource::collection(
            $this->payrollService->listRuns([
                'year' => $request->query('year'),
                'status' => $request->query('status'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function store(StorePayrollRunRequest $request): PayrollRunResource
    {
        return new PayrollRunResource(
            $this->payrollService->createRun(
                month: $request->validated('month'),
                year: $request->validated('year'),
            ),
        );
    }

    public function show(PayrollRun $payrollRun): PayrollRunResource
    {
        return new PayrollRunResource(
            $this->payrollService->showRun($payrollRun),
        );
    }

    public function destroy(PayrollRun $payrollRun): JsonResponse
    {
        $this->payrollService->deleteRun($payrollRun);

        return response()->json(['message' => 'Payroll run deleted successfully.']);
    }

    public function calculate(PayrollRun $payrollRun): PayrollRunResource
    {
        return new PayrollRunResource(
            $this->payrollService->calculate($payrollRun),
        );
    }

    public function approve(PayrollRun $payrollRun): PayrollRunResource
    {
        return new PayrollRunResource(
            $this->payrollService->approve($payrollRun),
        );
    }

    public function markPaid(PayrollRun $payrollRun): PayrollRunResource
    {
        return new PayrollRunResource(
            $this->payrollService->markPaid($payrollRun),
        );
    }

    public function items(PayrollRun $payrollRun): AnonymousResourceCollection
    {
        return PayrollItemResource::collection(
            $payrollRun->items()->with('employee.user')->get(),
        );
    }
}
