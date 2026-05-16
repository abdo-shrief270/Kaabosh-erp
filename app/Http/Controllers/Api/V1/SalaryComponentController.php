<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\SalaryComponent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\AssignSalaryComponentRequest;
use App\Http\Requests\Payroll\StoreSalaryComponentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $components = SalaryComponent::where('tenant_id', app('tenant.id'))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->paginate(min((int) ($request->query('per_page', 15)), 100));

        return $this->success($components);
    }

    public function store(StoreSalaryComponentRequest $request): JsonResponse
    {
        $component = SalaryComponent::create([
            'tenant_id' => app('tenant.id'),
            ...$request->validated(),
        ]);

        return $this->created($component);
    }

    public function update(StoreSalaryComponentRequest $request, SalaryComponent $salaryComponent): JsonResponse
    {
        $salaryComponent->update($request->validated());

        return $this->success($salaryComponent->fresh());
    }

    public function destroy(SalaryComponent $salaryComponent): JsonResponse
    {
        $salaryComponent->delete();

        return $this->deleted('تم حذف مكون الراتب بنجاح.');
    }

    public function assign(AssignSalaryComponentRequest $request, Employee $employee): JsonResponse
    {
        $detail = $employee->salaryDetails()->create([
            'tenant_id' => app('tenant.id'),
            ...$request->validated(),
        ]);

        return $this->created($detail);
    }

    public function employeeComponents(Employee $employee): JsonResponse
    {
        $details = $employee->salaryDetails()
            ->with('salaryComponent')
            ->orderByDesc('effective_from')
            ->get();

        return $this->success($details);
    }
}
