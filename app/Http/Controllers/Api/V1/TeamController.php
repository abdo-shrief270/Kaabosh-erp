<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Services\PermissionService;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Team\Services\TeamService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\InviteTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TeamMemberResource::collection(
            $this->teamService->list([
                'search' => $request->query('search'),
                'role' => $request->query('role'),
                'is_active' => $request->query('is_active') !== null
                    ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)
                    : null,
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function invite(InviteTeamMemberRequest $request): TeamMemberResource
    {
        $user = $this->teamService->invite(
            email: $request->validated('email'),
            name: $request->validated('name'),
            role: $request->validated('role', 'accountant'),
        );

        return new TeamMemberResource($user);
    }

    public function update(UpdateTeamMemberRequest $request, User $user): TeamMemberResource
    {
        return new TeamMemberResource(
            $this->teamService->update($user, $request->validated()),
        );
    }

    public function toggleActive(User $user): TeamMemberResource
    {
        return new TeamMemberResource(
            $this->teamService->toggleActive($user),
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->teamService->remove($user);

        return response()->json(['message' => 'Team member removed successfully.']);
    }

    public function assignRole(Request $request, User $user): TeamMemberResource
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        PermissionService::assignRole($user, $request->input('role'));

        // Also update the UserRole enum field for backward compat
        $enumRole = UserRole::tryFrom($request->input('role'));
        if ($enumRole) {
            $user->update(['role' => $enumRole]);
        }

        return new TeamMemberResource($user->refresh());
    }
}
