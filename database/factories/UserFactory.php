<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\UserRole;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'role' => UserRole::Client,
            'locale' => 'ar',
            'timezone' => null,
            'client_id' => null,
            'is_active' => true,
            'two_factor_enabled' => false,
            'remember_token' => Str::random(10),
            'last_login_at' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'tenant_id' => null,
            'role' => UserRole::SuperAdmin,
            'two_factor_enabled' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin,
            'two_factor_enabled' => true,
        ]);
    }

    public function accountant(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Accountant,
        ]);
    }

    public function auditor(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Auditor,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
