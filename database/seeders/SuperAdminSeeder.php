<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'ceo@muhasebi.com'],
            [
                'name' => 'Abdo Shrief',
                'password' => Hash::make('2510885891'),
                'role' => UserRole::SuperAdmin,
                'tenant_id' => null,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
