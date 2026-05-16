<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\TaskBoard\Events\TaskMoved as TaskMovedEvent;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskApprovalRequest;
use App\Domain\TaskBoard\Services\BoardAccessService;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Domain\TaskBoard\Services\TaskMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approval gate flow:
 *
 *   1. Client moves a task into a column with requires_approval = true.
 *      TaskMovementService rejects with 422 + error='approval_required'.
 *   2. Client follows up with POST /tasks/{task}/approval-requests
 *      pointing at the same target_column_id. We persist the pending
 *      request and notify the board's admins.
 *   3. Any board admin (or tenant manage_boards holder) POSTs
 *      /task-approval-requests/{id}/decision with status=approved|rejected.
 *      Approval re-runs the move with skip_approval=true.
 *
 * Requesters can cancel their own pending requests.
 */
class TaskApprovalController extends Controller
{
    public function __construct(
        private readonly BoardAccessService $access,
        private readonly TaskMovementService $movement,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->can('view_tasks'), 403);

        $status = $request->string('status')->toString() ?: 'pending';
        $boardId = $request->integer('board_id');
        $scope = $request->string('scope')->toString(); // 'mine' (requested by me) | 'queue' (to decide)

        $query = TaskApprovalRequest::query()
            ->where('tenant_id', app('tenant.id'))
            ->where('status', $status)
            ->with([
                'task:id,reference,title,board_id,board_column_id',
                'fromColumn:id,name',
                'targetColumn:id,name,board_id',
                'requestedBy:id,name,email',
                'decidedBy:id,name,email',
            ])
            ->latest('id');

        if ($boardId) {
            $query->whereHas('task', fn ($q) => $q->where('board_id', $boardId));
        }
        if ($scope === 'mine') {
            $query->where('requested_by_user_id', $user->id);
        } elseif ($scope === 'queue') {
            // Boards the user is admin on, PLUS columns that name the user
            // as an explicit approver. Either path is enough to put a
            // pending request in their queue.
            $boardIds = $this->approverBoardIds($user);
            $uid = (int) $user->id;
            $query->where(function ($q) use ($boardIds, $uid) {
                $q->whereHas('targetColumn', fn ($c) => $c->whereIn('board_id', $boardIds))
                    ->orWhereHas('targetColumn', fn ($c) =>
                        $c->whereJsonContains('approver_user_ids', $uid)
                          ->orWhereJsonContains('approver_user_ids', (string) $uid),
                    );
            });
        }

        $items = $query->limit(100)->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->access->has($task->board, $user, BoardMember::LEVEL_EDITOR), 403);

        $data = $request->validate([
            'target_column_id' => ['required', 'integer', 'exists:board_columns,id,tenant_id,'.app('tenant.id').',board_id,'.$task->board_id],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $column = BoardColumn::findOrFail($data['target_column_id']);
        abort_unless($column->requires_approval, 422, 'Target column does not require approval.');
        abort_if($task->board_column_id === $column->id, 422, 'Task is already in the target column.');

        // Refuse if a pending request already exists for this task.
        $exists = TaskApprovalRequest::query()
            ->where('task_id', $task->id)
            ->where('status', TaskApprovalRequest::STATUS_PENDING)
            ->exists();
        abort_if($exists, 409, 'A pending approval already exists for this task.');

        $req = TaskApprovalRequest::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'from_column_id' => $task->board_column_id,
            'target_column_id' => $column->id,
            'requested_by_user_id' => $user->id,
            'status' => TaskApprovalRequest::STATUS_PENDING,
            'reason' => $data['reason'] ?? null,
        ]);

        $this->notifyApprovers($task, $req, $user->id);

        return response()->json(['data' => $req->fresh()->load(['targetColumn', 'fromColumn'])], 201);
    }

    public function decide(Request $request, TaskApprovalRequest $approvalRequest, TaskActivityService $activity): JsonResponse
    {
        $user = $request->user();
        $board = $approvalRequest->task?->board;
        abort_unless($board, 404);

        // Authorised approvers: board admins, or — when the target column
        // has an explicit approver_user_ids list — anyone on that list.
        $column = $approvalRequest->targetColumn ?? \App\Domain\TaskBoard\Models\BoardColumn::find($approvalRequest->target_column_id);
        $explicit = (array) ($column?->approver_user_ids ?? []);
        $isExplicitApprover = $explicit && in_array((int) $user->id, array_map('intval', $explicit), true);
        $isBoardAdmin = $this->access->has($board, $user, BoardMember::LEVEL_ADMIN);
        abort_unless($isBoardAdmin || $isExplicitApprover, 403);

        abort_unless($approvalRequest->status === TaskApprovalRequest::STATUS_PENDING, 422, 'Request is already decided.');

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($approvalRequest, $data, $user, $activity) {
            $approvalRequest->update([
                'status' => $data['status'],
                'decided_by_user_id' => $user->id,
                'decided_at' => now(),
                'reason' => $data['reason'] ?? $approvalRequest->reason,
            ]);

            if ($data['status'] === TaskApprovalRequest::STATUS_APPROVED) {
                $task = $approvalRequest->task;
                $target = $approvalRequest->targetColumn;
                if (! $task || ! $target) return;
                // The task may have moved elsewhere while waiting; only
                // apply if it's still where it was when requested.
                if ($task->board_column_id !== (int) $approvalRequest->from_column_id) {
                    return;
                }
                $fromColumnId = (int) $task->board_column_id;
                $moved = $this->movement->moveTask($task, [
                    'column_id' => $target->id,
                    'skip_approval' => true,
                ]);
                $activity->moved($moved, (int) $user->id, $fromColumnId, (int) $target->id);
                TaskMovedEvent::dispatch(
                    $moved, $fromColumnId, (int) $target->id,
                    (bool) $moved->completed_at, $user->id,
                );
            }
        });

        // Notify the requester of the outcome.
        if ($approvalRequest->requested_by_user_id && $approvalRequest->requested_by_user_id !== $user->id) {
            $verb = $data['status'] === TaskApprovalRequest::STATUS_APPROVED ? 'approved' : 'rejected';
            $taskRef = $approvalRequest->task?->reference ?? 'a task';
            $this->notifications->send(
                userId: (int) $approvalRequest->requested_by_user_id,
                type: NotificationType::ApprovalDecided,
                titleAr: 'تم الفصل في طلبك على '.$taskRef,
                titleEn: "Your request on $taskRef was $verb",
                bodyAr: (string) ($data['reason'] ?? ''),
                bodyEn: (string) ($data['reason'] ?? ''),
                actionUrl: $this->taskUrl($approvalRequest->task_id),
                data: ['task_id' => $approvalRequest->task_id, 'approval_request_id' => $approvalRequest->id, 'status' => $data['status']],
                channel: NotificationChannel::InApp,
            );
        }

        return response()->json(['data' => $approvalRequest->fresh()]);
    }

    public function cancel(Request $request, TaskApprovalRequest $approvalRequest): JsonResponse
    {
        $user = $request->user();
        $isRequester = $approvalRequest->requested_by_user_id === $user->id;
        $isAdmin = $approvalRequest->task && $this->access->has(
            $approvalRequest->task->board, $user, BoardMember::LEVEL_ADMIN,
        );
        abort_unless($isRequester || $isAdmin, 403);
        abort_unless($approvalRequest->status === TaskApprovalRequest::STATUS_PENDING, 422, 'Request is already decided.');

        $approvalRequest->update([
            'status' => TaskApprovalRequest::STATUS_CANCELLED,
            'decided_by_user_id' => $user->id,
            'decided_at' => now(),
        ]);

        return response()->json(['data' => $approvalRequest->fresh()]);
    }

    /** @return array<int, int> */
    private function approverBoardIds($user): array
    {
        if ($user->can('manage_boards')) {
            return DB::table('boards')->where('tenant_id', $user->tenant_id)
                ->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        return BoardMember::query()
            ->where('user_id', $user->id)
            ->where('level', BoardMember::LEVEL_ADMIN)
            ->pluck('board_id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Approver pool resolution, ordered priority:
     *   1. If the target column has approver_user_ids configured, use that.
     *   2. Otherwise, board admins.
     *   3. As a last-resort fallback (unmanaged board with no admins),
     *      tenant manage_boards holders.
     */
    private function approverIdsFor(\App\Domain\TaskBoard\Models\BoardColumn $column, Task $task): array
    {
        $explicit = $column->approver_user_ids ?? [];
        if (! empty($explicit)) {
            return array_values(array_unique(array_map('intval', $explicit)));
        }

        $admins = BoardMember::query()
            ->where('board_id', $task->board_id)
            ->where('level', BoardMember::LEVEL_ADMIN)
            ->pluck('user_id')->map(fn ($v) => (int) $v)->all();

        if (! empty($admins)) return $admins;

        if (Schema::hasTable('roles')) {
            return DB::table('model_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
                ->where('permissions.name', 'manage_boards')
                ->where('model_has_permissions.model_type', \App\Models\User::class)
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('users')
                    ->whereColumn('users.id', 'model_has_permissions.model_id')
                    ->where('users.tenant_id', $task->tenant_id))
                ->pluck('model_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
        }
        return [];
    }

    private function notifyApprovers(Task $task, TaskApprovalRequest $req, int $actorId): void
    {
        $column = $req->targetColumn ?? \App\Domain\TaskBoard\Models\BoardColumn::find($req->target_column_id);
        if (! $column) return;
        $approverIds = $this->approverIdsFor($column, $task);

        foreach ($approverIds as $uid) {
            if ($uid === $actorId) continue;
            $this->notifications->send(
                userId: $uid,
                type: NotificationType::ApprovalRequired,
                titleAr: 'طلب موافقة على نقل '.$task->reference,
                titleEn: 'Approval needed to move '.$task->reference,
                bodyAr: $task->title,
                bodyEn: $task->title,
                actionUrl: $this->taskUrl($task->id),
                data: ['task_id' => $task->id, 'approval_request_id' => $req->id],
                channel: NotificationChannel::InApp,
            );
        }
    }

    private function taskUrl(?int $taskId): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        return $base.'/tasks/'.($taskId ?? '');
    }
}
