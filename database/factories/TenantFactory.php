<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'domain' => null,
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_id' => null,
            'commercial_register' => (string) fake()->numberBetween(10000, 99999),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'status' => TenantStatus::Active,
            'settings' => [
                'locale' => 'ar',
                'timezone' => 'Africa/Cairo',
                'currency' => 'EGP',
                'fiscal_year_start' => '01-01',
            ],
            'trial_ends_at' => null,
            'logo_path' => null,
        ];
    }

    public function trial(int $daysRemaining = 14): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Trial,
            'trial_ends_at' => now()->addDays($daysRemaining),
        ]);
    }

    public function expiredTrial(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Trial,
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Suspended,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Cancelled,
        ]);
    }
}
