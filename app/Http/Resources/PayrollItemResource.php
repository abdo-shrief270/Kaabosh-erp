<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Payroll\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayrollItem */
class PayrollItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'base_salary' => $this->base_salary,
            'allowances' => $this->allowances,
            'overtime_hours' => $this->overtime_hours,
            'overtime_amount' => $this->overtime_amount,
            'gross_salary' => $this->gross_salary,
            'social_insurance_employee' => $this->social_insurance_employee,
            'social_insurance_employer' => $this->social_insurance_employer,
            'taxable_income' => $this->taxable_income,
            'income_tax' => $this->income_tax,
            'other_deductions' => $this->other_deductions,
            'net_salary' => $this->net_salary,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),

            'employee' => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }
}
