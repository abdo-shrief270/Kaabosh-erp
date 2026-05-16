<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Lightweight role list for the @-mention popover. Same tenant scoping as
 * the parser (filters by spatie's `team_id` column when present). Public
 * to anyone who can view tasks — role NAMES are not sensitive, while
 * the full RBAC admin UI stays gated by `manage_roles`.
 */
class MentionRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $query = Role::query()->orderBy('name');
        if (Schema::hasColumn('roles', 'team_id')) {
            $query->where('team_id', app('tenant.id'));
        }

        $roles = $query->limit(50)->get(['id', 'name'])->map(fn ($r) => [
            'id' => (int) $r->id,
            'name' => $r->name,
        ]);

        return response()->json(['data' => $roles]);
    }
}
