<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\BoardCustomField;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Domain\TaskBoard\Models\Sprint;
use App\Domain\TaskBoard\Models\Tag;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskApprovalRequest;
use App\Domain\TaskBoard\Models\TaskAttachment;
use App\Domain\TaskBoard\Models\TaskChecklist;
use App\Domain\TaskBoard\Models\TaskChecklistItem;
use App\Domain\TaskBoard\Models\TaskComment;
use App\Domain\TaskBoard\Models\TaskDependency;
use App\Domain\TaskBoard\Models\TaskRecurrence;
use App\Domain\TaskBoard\Models\TaskTimeEntry;
use App\Domain\TaskBoard\Models\TaskWorkflowTransition;
use App\Domain\TaskBoard\Models\Version;
use App\Domain\TaskBoard\Services\BoardAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level guard for board-scoped operations. Reads the bound model from
 * the route (Task, BoardColumn, TaskComment, …), resolves the owning
 * board, and rejects with 403 when the requester's BoardAccessService
 * level is below `level`.
 *
 * Usage in routes:
 *   ->middleware('board.access:editor')
 *
 * Falls back silently (no-op) when no bound model is recognisable — the
 * controller's existing tenant-RBAC checks still apply, so we degrade
 * to today's behaviour rather than locking everyone out.
 */
class EnsureBoardAccess
{
    public function __construct(private readonly BoardAccessService $access) {}

    public function handle(Request $request, Closure $next, string $level = 'viewer'): Response
    {
        $user = $request->user();
        if (! $user) return $next($request); // sanctum middleware handles auth elsewhere

        $board = $this->resolveBoard($request);
        if (! $board) {
            // No model bound that we can map to a board. Most write
            // endpoints will instead pass `board_id` in the body; we
            // check that case here as well.
            $boardIdParam = $request->input('board_id');
            if ($boardIdParam) {
                $board = Board::query()->whereKey((int) $boardIdParam)->first();
            }
        }
        if (! $board) return $next($request);

        if (! $this->access->has($board, $user, $level)) {
            abort(403, "You don't have $level access to this board.");
        }

        return $next($request);
    }

    private function resolveBoard(Request $request): ?Board
    {
        $route = $request->route();
        if (! $route) return null;

        // Direct match: {board}
        $board = $route->parameter('board');
        if ($board instanceof Board) return $board;

        // Task & task-sub-resources
        $task = $route->parameter('task');
        if ($task instanceof Task && $task->board_id) {
            return Board::find($task->board_id);
        }

        $column = $route->parameter('column');
        if ($column instanceof BoardColumn && $column->board_id) {
            return Board::find($column->board_id);
        }

        $customField = $route->parameter('customField');
        if ($customField instanceof BoardCustomField && $customField->board_id) {
            return Board::find($customField->board_id);
        }

        $comment = $route->parameter('comment');
        if ($comment instanceof TaskComment) {
            return Board::find($comment->task?->board_id);
        }

        $attachment = $route->parameter('attachment');
        if ($attachment instanceof TaskAttachment) {
            return Board::find($attachment->task?->board_id);
        }

        $member = $route->parameter('member');
        if ($member instanceof BoardMember && $member->board_id) {
            return Board::find($member->board_id);
        }

        $approvalRequest = $route->parameter('approvalRequest');
        if ($approvalRequest instanceof TaskApprovalRequest) {
            return Board::find($approvalRequest->task?->board_id);
        }

        $transition = $route->parameter('transition');
        if ($transition instanceof TaskWorkflowTransition && $transition->board_id) {
            return Board::find($transition->board_id);
        }

        $checklist = $route->parameter('checklist');
        if ($checklist instanceof TaskChecklist) {
            return Board::find($checklist->task?->board_id);
        }

        $item = $route->parameter('item');
        if ($item instanceof TaskChecklistItem) {
            return Board::find($item->checklist?->task?->board_id);
        }

        $sprint = $route->parameter('sprint');
        if ($sprint instanceof Sprint && $sprint->board_id) {
            return Board::find($sprint->board_id);
        }

        $tag = $route->parameter('tag');
        if ($tag instanceof Tag && $tag->board_id) {
            return Board::find($tag->board_id);
        }

        $version = $route->parameter('version');
        if ($version instanceof Version && $version->board_id) {
            return Board::find($version->board_id);
        }

        $taskTimeEntry = $route->parameter('taskTimeEntry');
        if ($taskTimeEntry instanceof TaskTimeEntry) {
            return Board::find($taskTimeEntry->task?->board_id);
        }

        $taskRecurrence = $route->parameter('taskRecurrence');
        if ($taskRecurrence instanceof TaskRecurrence && $taskRecurrence->board_id) {
            return Board::find($taskRecurrence->board_id);
        }

        $taskDependency = $route->parameter('taskDependency');
        if ($taskDependency instanceof TaskDependency) {
            return Board::find($taskDependency->task?->board_id);
        }

        return null;
    }
}
