<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    /**
     * Get all permissions for a user.
     * Uses Spatie DB if user has roles assigned, falls back to config.
     *
     * @return list<string>
     */
    public static function getUserPermissions(User $user): array
    {
        if ($user->role === UserRole::SuperAdmin) {
            return Permission::pluck('name')->toArray()
                ?: collect(config('permissions', []))->flatten()->unique()->values()->all();
        }

        // Try Spatie DB first
        $spatiePermissions = $user->getAllPermissions()->pluck('name')->toArray();

        if (! empty($spatiePermissions)) {
            return $spatiePermissions;
        }

        // Fallback to config for users without Spatie roles assigned
        return config("permissions.{$user->role->value}", []);
    }

    /**
     * Check if a user has a specific permission.
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        // Try Spatie first
        try {
            if ($user->roles->isNotEmpty()) {
                return $user->hasPermissionTo($permission);
            }
        } catch (\Throwable) {
            // Permission not found in DB — fall through to config
        }

        // Fallback to config
        $permissions = config("permissions.{$user->role->value}", []);

        return in_array($permission, $permissions, true);
    }

    /**
     * Get all permissions for a role (by name).
     *
     * @return list<string>
     */
    public static function getPermissionsForRole(string $roleName): array
    {
        $role = Role::findByName($roleName, 'web');

        return $role ? $role->permissions->pluck('name')->toArray() : [];
    }

    /**
     * Assign a Spatie role to a user. Removes any previous roles.
     */
    public static function assignRole(User $user, string $roleName): void
    {
        $user->syncRoles([$roleName]);
    }

    /**
     * Get all available roles.
     *
     * @return Collection
     */
    public static function getAllRoles()
    {
        return Role::where('guard_name', 'web')->withCount('permissions', 'users')->get();
    }

    /**
     * Get all available permissions.
     *
     * @return Collection
     */
    public static function getAllPermissions()
    {
        return Permission::where('guard_name', 'web')->orderBy('name')->get();
    }

    /**
     * Create a new role with permissions.
     */
    public static function createRole(string $name, array $permissions = []): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role;
    }

    /**
     * Update a role's permissions.
     */
    public static function updateRole(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->load('permissions');
    }

    /**
     * Delete a role if no users are assigned to it.
     *
     * @throws ValidationException
     */
    public static function deleteRole(Role $role): void
    {
        if ($role->users()->count() > 0) {
            throw ValidationException::withMessages([
                'role' => [
                    'Cannot delete role with assigned users.',
                    'لا يمكن حذف دور به مستخدمين.',
                ],
            ]);
        }

        $role->delete();
    }
}
