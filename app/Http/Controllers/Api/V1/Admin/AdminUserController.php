<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Admin\Services\AdminUserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCreateSuperAdminRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $userService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminUserResource::collection(
            $this->userService->list([
                'role' => $request->query('role'),
                'is_active' => $request->query('is_active') !== null
                    ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)
                    : null,
                'search' => $request->query('search'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function createSuperAdmin(AdminCreateSuperAdminRequest $request): AdminUserResource
    {
        return new AdminUserResource(
            $this->userService->createSuperAdmin($request->validated()),
        );
    }

    public function toggleActive(int $userId): AdminUserResource
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);

        return new AdminUserResource(
            $this->userService->toggleActive($user),
        );
    }
}
