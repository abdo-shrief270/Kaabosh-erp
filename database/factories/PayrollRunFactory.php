<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Enums\PayrollStatus;
use App\Domain\Payroll\Models\PayrollRun;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRun> */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'month' => (int) now()->format('m'),
            'year' => (int) now()->format('Y'),
            'status' => PayrollStatus::Draft,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn () => ['status' => PayrollStatus::Calculated]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => PayrollStatus::Approved]);
    }
}
