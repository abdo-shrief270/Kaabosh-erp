<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Payroll\Models\SocialInsuranceRate;
use Illuminate\Database\Seeder;

class SocialInsuranceRateSeeder extends Seeder
{
    public function run(): void
    {
        SocialInsuranceRate::updateOrCreate(
            ['year' => 2025],
            [
                'basic_employee_rate' => '0.1100',
                'basic_employer_rate' => '0.1875',
                'variable_employee_rate' => '0.1100',
                'variable_employer_rate' => '0.1875',
                'basic_max_salary' => '12600.00',
                'variable_max_salary' => '10500.00',
                'minimum_subscription' => '2100.00',
                'effective_from' => '2025-01-01',
            ],
        );
    }
}
