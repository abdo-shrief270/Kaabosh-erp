<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tax\Enums\WhtCertificateStatus;
use App\Domain\Tax\Models\WhtCertificate;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WhtCertificate> */
class WhtCertificateFactory extends Factory
{
    protected $model = WhtCertificate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $from = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'tenant_id' => Tenant::factory(),
            'certificate_number' => 'WHT-'.fake()->unique()->numerify('######'),
            'vendor_name' => fake()->company(),
            'vendor_tax_id' => fake()->numerify('#########'),
            'period_from' => $from,
            'period_to' => fake()->dateTimeBetween($from, 'now'),
            'total_wht_amount' => fake()->randomFloat(2, 100, 50000),
            'status' => WhtCertificateStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function issued(): static
    {
        return $this->state([
            'status' => WhtCertificateStatus::Issued,
            'issued_at' => now(),
        ]);
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => WhtCertificateStatus::Submitted,
            'issued_at' => now()->subDays(7),
            'submitted_at' => now(),
        ]);
    }
}
