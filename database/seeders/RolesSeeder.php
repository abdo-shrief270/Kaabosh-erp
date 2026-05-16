<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds tenant-level roles. Runs after PermissionSeeder so all permissions
 * named in config('permissions') already exist.
 *
 * Idempotent: re-running syncs the role→permission map in place.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'tenant_admin' => config('permissions.admin', []),
            'tenant_accountant' => config('permissions.accountant', []),
            'tenant_auditor' => config('permissions.auditor', []),
            'tenant_limited' => ['manage_clients', 'manage_documents'],
        ];

        foreach ($roles as $roleName => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web'],
                );
            }

            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            $role->syncPermissions($permissions);
        }
    }
}
