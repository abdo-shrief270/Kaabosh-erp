<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Per-board access resolver. Sits on top of the tenant-level RBAC, not in
 * place of it. The model:
 *
 *   1. If a board has zero rows in board_members, access falls back to the
 *      tenant role (current behaviour — viewing requires `view_tasks` etc).
 *      This keeps every existing board behaving as it does today.
 *
 *   2. Once any member row exists for a board, the board is "managed":
 *      only listed members can interact with it, at the level recorded.
 *      Tenant-level `manage_boards` still grants admin everywhere (escape
 *      hatch for owners).
 *
 * Levels (weakest → strongest):
 *   viewer  → can list/view tasks
 *   editor  → + create/edit/move/delete tasks, comments, attachments
 *   admin   → + manage columns, settings, members, workflow rules
 */
class BoardAccessService
{
    /**
     * Resolve a user's effective level on a board. Returns null when the
     * user has no access at all.
     */
    public function levelFor(Board $board, User $user): ?string
    {
        // Tenant-level escape hatch — workspace admins implicitly own
        // every board, even when explicit members exist.
        if ($user->can('manage_boards')) {
            return BoardMember::LEVEL_ADMIN;
        }

        $explicit = BoardMember::query()
            ->where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->value('level');

        if ($explicit) {
            return $explicit;
        }

        // No row for this user. Did the board opt into membership scoping?
        $managed = BoardMember::query()->where('board_id', $board->id)->exists();
        if ($managed) {
            return null; // strict: managed boards reject non-members
        }

        // Unmanaged board → fall back to tenant role.
        if ($user->can('edit_tasks')) return BoardMember::LEVEL_EDITOR;
        if ($user->can('view_tasks')) return BoardMember::LEVEL_VIEWER;

        return null;
    }

    /**
     * `requiredLevel` is one of the LEVEL_* constants. Returns true when
     * the user's effective level is ≥ required.
     */
    public function has(Board $board, User $user, string $requiredLevel): bool
    {
        $effective = $this->levelFor($board, $user);
        if (! $effective) return false;
        return (BoardMember::LEVEL_ORDER[$effective] ?? 0)
            >= (BoardMember::LEVEL_ORDER[$requiredLevel] ?? 99);
    }

    /**
     * List the board ids accessible to a user. Used by BoardController::index
     * to filter the visible board list — without this, every tenant member
     * would see board names they aren't allowed to open.
     *
     * @return array<int, int>
     */
    public function accessibleBoardIds(User $user): array
    {
        if ($user->can('manage_boards')) {
            // Workspace admin sees everything in the tenant.
            return Board::query()
                ->where('tenant_id', $user->tenant_id)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Boards the user is an explicit member of.
        $memberBoards = BoardMember::query()
            ->where('user_id', $user->id)
            ->pluck('board_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        // Plus every unmanaged board (= boards with zero member rows) the
        // user can view via tenant RBAC.
        $canViewTenant = (bool) $user->can('view_tasks');
        if (! $canViewTenant) {
            return $memberBoards;
        }

        $unmanaged = DB::table('boards')
            ->where('boards.tenant_id', $user->tenant_id)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('board_members')
                ->whereColumn('board_members.board_id', 'boards.id'))
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return array_values(array_unique(array_merge($memberBoards, $unmanaged)));
    }
}
