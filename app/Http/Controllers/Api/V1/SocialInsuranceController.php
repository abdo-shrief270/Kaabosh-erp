<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Services\SocialInsuranceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialInsuranceController extends Controller
{
    public function __construct(
        private readonly SocialInsuranceService $service,
    ) {}

    /**
     * Calculate social insurance for an employee.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'month' => ['required', 'string', 'date_format:Y-m'],
        ]);

        $breakdown = $this->service->calculate($validated['employee_id'], $validated['month']);

        return $this->success($breakdown);
    }

    /**
     * Generate SISA monthly report.
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'string', 'date_format:Y-m'],
        ]);

        $report = $this->service->monthlyReport($validated['month']);

        return $this->success($report);
    }

    /**
     * Register or update employee insurance record.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'insurance_number' => ['nullable', 'string', 'max:30'],
            'registration_date' => ['nullable', 'date'],
            'insurance_type' => ['nullable', 'string', 'in:regular,trainee,foreigner,exempted'],
            'basic_insurance_salary' => ['required', 'numeric', 'min:0'],
            'variable_insurance_salary' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = $this->service->registerEmployee($validated['employee_id'], $validated);

        return $this->success($record, 'Success', 201);
    }

    /**
     * Get social insurance rates for a given year.
     */
    public function rates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        // Query-string inputs stay as strings after validate(); cast to int
        // so the service's strict int parameter typing is satisfied.
        $rates = $this->service->getRates((int) $validated['year']);

        return $this->success($rates);
    }
}
