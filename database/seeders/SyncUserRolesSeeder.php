<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs existing users' UserRole enum to Spatie roles.
 * Safe to run multiple times.
 */
class SyncUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $users = User::withoutGlobalScopes()->get();
        $synced = 0;

        foreach ($users as $user) {
            // Skip super admins and clients — they don't need Spatie roles
            if ($user->role === UserRole::SuperAdmin || $user->role === UserRole::Client) {
                continue;
            }

            $roleName = $user->role->value; // 'admin', 'accountant', 'auditor'

            if (! $user->hasRole($roleName)) {
                try {
                    $user->assignRole($roleName);
                    $synced++;
                } catch (\Throwable $e) {
                    // Role might not exist — skip
                }
            }
        }

        $this->command?->info("Synced {$synced} users to Spatie roles.");
    }
}
