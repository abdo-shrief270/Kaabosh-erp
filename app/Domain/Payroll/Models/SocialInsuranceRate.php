<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('social_insurance_rates')]
#[Fillable([
    'year',
    'basic_employee_rate',
    'basic_employer_rate',
    'variable_employee_rate',
    'variable_employer_rate',
    'basic_max_salary',
    'variable_max_salary',
    'minimum_subscription',
    'effective_from',
])]
class SocialInsuranceRate extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'basic_employee_rate' => 'decimal:4',
            'basic_employer_rate' => 'decimal:4',
            'variable_employee_rate' => 'decimal:4',
            'variable_employer_rate' => 'decimal:4',
            'basic_max_salary' => 'decimal:2',
            'variable_max_salary' => 'decimal:2',
            'minimum_subscription' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }
}
