<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Auth\Services\PermissionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(
            PermissionService::getAllRoles(),
        );
    }

    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = PermissionService::createRole(
            $request->validated('name'),
            $request->validated('permissions', []),
        );

        return new RoleResource($role->load('permissions'));
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions')->loadCount('users'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        if ($request->has('name')) {
            $role->update(['name' => $request->validated('name')]);
        }

        if ($request->has('permissions')) {
            PermissionService::updateRole($role, $request->validated('permissions'));
        }

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        PermissionService::deleteRole($role);

        return response()->json(['message' => 'Role deleted.']);
    }

    public function permissions(): AnonymousResourceCollection
    {
        return PermissionResource::collection(
            PermissionService::getAllPermissions(),
        );
    }
}
