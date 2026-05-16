<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskComment;
use App\Domain\TaskBoard\Services\MentionParser;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Domain\TaskBoard\Services\TaskNotificationDispatcher;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskCommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TaskCommentController extends Controller
{
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $comments = $task->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name,email', 'replies.user:id,name,email', 'attachments'])
            ->orderBy('created_at')
            ->paginate(50);

        return TaskCommentResource::collection($comments);
    }

    public function store(
        Request $request,
        Task $task,
        MentionParser $mentions,
        TaskActivityService $activity,
        TaskNotificationDispatcher $notify,
    ): JsonResource {
        abort_unless($request->user()?->can('comment_tasks'), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        // Enforce single-level threading: a reply's parent must itself be top-level.
        if (! empty($data['parent_id'])) {
            $parent = TaskComment::query()
                ->where('task_id', $task->id)
                ->whereKey($data['parent_id'])
                ->firstOrFail();
            if ($parent->parent_id !== null) {
                return response()->json(['message' => 'Cannot reply to a reply.'], 422);
            }
        }

        $mentionedIds = $mentions->extract($data['body'], (int) app('tenant.id'));

        /** @var TaskComment $comment */
        $comment = TaskComment::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'mentions' => $mentionedIds,
        ]);

        // Auto-watch on first comment so the commenter stays in the loop.
        $task->watchers()->syncWithoutDetaching([$request->user()->id]);

        $activity->comment(
            task: $task,
            userId: (int) $request->user()->id,
            commentId: $comment->id,
            bodyPreview: Str::limit(strip_tags($comment->body), 120),
        );

        $notify->commentPosted($task, $comment);

        \App\Domain\TaskBoard\Events\CommentAdded::dispatch(
            $comment,
            (int) $task->board_id,
            (int) $request->user()->id,
        );

        return TaskCommentResource::make($comment->load('user'));
    }

    public function update(Request $request, TaskComment $comment, MentionParser $mentions): JsonResource
    {
        abort_unless(
            $request->user()?->id === $comment->user_id && $request->user()?->can('comment_tasks'),
            403,
        );

        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        $comment->update([
            'body' => $data['body'],
            'mentions' => $mentions->extract($data['body'], $comment->tenant_id),
            'edited_at' => now(),
        ]);

        return TaskCommentResource::make($comment->fresh('user'));
    }

    public function destroy(Request $request, TaskComment $comment)
    {
        $isOwner = $request->user()?->id === $comment->user_id;
        abort_unless($isOwner || $request->user()?->can('delete_tasks'), 403);

        $commentId = $comment->id;
        $taskId = $comment->task_id;
        $task = $comment->task;
        $boardId = (int) ($task?->board_id ?? 0);

        $comment->delete();

        if ($boardId) {
            \App\Domain\TaskBoard\Events\CommentDeleted::dispatch(
                $commentId,
                $taskId,
                $boardId,
                (int) $request->user()->id,
            );
        }

        return response()->noContent();
    }
}
