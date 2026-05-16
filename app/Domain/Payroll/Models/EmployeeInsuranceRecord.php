<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('employee_insurance_records')]
#[Fillable([
    'tenant_id',
    'employee_id',
    'insurance_number',
    'registration_date',
    'insurance_type',
    'basic_insurance_salary',
    'variable_insurance_salary',
    'is_active',
    'notes',
])]
class EmployeeInsuranceRecord extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'basic_insurance_salary' => 'decimal:2',
            'variable_insurance_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
