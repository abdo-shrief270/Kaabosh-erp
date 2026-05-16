<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Domain\TaskBoard\Services\BoardAccessService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage board members. Only admins of the board (or tenant-level
 * manage_boards holders) can touch this endpoint.
 *
 * The endpoint also acts as the "lock" for membership-scoped access: as
 * soon as the first member row lands for a board, BoardAccessService
 * starts enforcing membership for everyone else.
 */
class BoardMemberController extends Controller
{
    public function __construct(private readonly BoardAccessService $access) {}

    public function index(Request $request, Board $board): JsonResponse
    {
        abort_unless($this->access->has($board, $request->user(), BoardMember::LEVEL_VIEWER), 403);

        $members = BoardMember::query()
            ->where('board_id', $board->id)
            ->with('user:id,name,email')
            ->get(['id', 'user_id', 'level', 'created_at']);

        return response()->json([
            'data' => $members->map(fn ($m) => [
                'id' => $m->id,
                'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name, 'email' => $m->user->email] : null,
                'level' => $m->level,
                'created_at' => $m->created_at,
            ]),
            'meta' => [
                'managed' => $members->isNotEmpty(),
                // For the SPA's "elevate me" UX: my own level.
                'my_level' => $this->access->levelFor($board, $request->user()),
            ],
        ]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        abort_unless($this->access->has($board, $request->user(), BoardMember::LEVEL_ADMIN), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id,tenant_id,'.app('tenant.id')],
            'level' => ['required', 'in:'.implode(',', BoardMember::LEVELS)],
        ]);

        // First-member promotion: if this is the very first row we're
        // creating on this board (i.e. the board is about to flip from
        // unmanaged → managed), auto-add the acting admin too. Without
        // this they'd lose their own access the moment they grant it to
        // someone else.
        $alreadyManaged = BoardMember::where('board_id', $board->id)->exists();
        $actorId = (int) $request->user()->id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($board, $data, $alreadyManaged, $actorId) {
            if (! $alreadyManaged && $data['user_id'] !== $actorId) {
                BoardMember::create([
                    'tenant_id' => $board->tenant_id,
                    'board_id' => $board->id,
                    'user_id' => $actorId,
                    'level' => BoardMember::LEVEL_ADMIN,
                ]);
            }

            BoardMember::updateOrCreate(
                ['board_id' => $board->id, 'user_id' => $data['user_id']],
                ['tenant_id' => $board->tenant_id, 'level' => $data['level']],
            );
        });

        $member = BoardMember::where('board_id', $board->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail()
            ->load('user:id,name,email');

        return response()->json(['data' => $member], 201);
    }

    public function update(Request $request, BoardMember $member): JsonResponse
    {
        $board = $member->board;
        abort_unless($board, 404);
        abort_unless($this->access->has($board, $request->user(), BoardMember::LEVEL_ADMIN), 403);

        $data = $request->validate([
            'level' => ['required', 'in:'.implode(',', BoardMember::LEVELS)],
        ]);

        // Prevent the last admin from demoting themselves into a non-admin
        // role and locking the board.
        $isLastAdmin = $member->level === BoardMember::LEVEL_ADMIN
            && $data['level'] !== BoardMember::LEVEL_ADMIN
            && BoardMember::where('board_id', $board->id)
                ->where('level', BoardMember::LEVEL_ADMIN)
                ->count() <= 1;
        abort_if($isLastAdmin, 422, 'A board needs at least one admin.');

        $member->update(['level' => $data['level']]);

        return response()->json(['data' => $member->fresh()->load('user:id,name,email')]);
    }

    public function destroy(Request $request, BoardMember $member)
    {
        $board = $member->board;
        abort_unless($board, 404);
        abort_unless($this->access->has($board, $request->user(), BoardMember::LEVEL_ADMIN), 403);

        // Same guard as update — don't strand a board without an admin.
        if ($member->level === BoardMember::LEVEL_ADMIN) {
            $remaining = BoardMember::where('board_id', $board->id)
                ->where('level', BoardMember::LEVEL_ADMIN)
                ->where('id', '!=', $member->id)
                ->count();
            abort_if($remaining === 0, 422, 'A board needs at least one admin.');
        }

        $member->delete();

        return response()->noContent();
    }
}
