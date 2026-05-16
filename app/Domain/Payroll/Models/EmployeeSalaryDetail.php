<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\CalculationType;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('employee_salary_details')]
#[Fillable([
    'tenant_id',
    'employee_id',
    'salary_component_id',
    'calculation_type',
    'amount',
    'percentage',
    'effective_from',
    'effective_to',
    'notes',
])]
class EmployeeSalaryDetail extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'calculation_type' => CalculationType::class,
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    /**
     * Calculate the effective amount based on the calculation type.
     * Fixed components return the raw amount; percentage components apply
     * the stored percentage to the relevant basis (basic or gross salary).
     */
    public function effectiveAmount(string $basicSalary = '0', string $grossSalary = '0'): string
    {
        return match ($this->calculation_type) {
            CalculationType::Fixed => (string) $this->amount,
            CalculationType::PercentageOfBasic => bcdiv(
                bcmul((string) $this->percentage, $basicSalary, 4),
                '100',
                2,
            ),
            CalculationType::PercentageOfGross => bcdiv(
                bcmul((string) $this->percentage, $grossSalary, 4),
                '100',
                2,
            ),
        };
    }
}
