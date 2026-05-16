<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Listeners;

use App\Domain\TaskBoard\Events\CommentAdded;
use App\Domain\TaskBoard\Events\TaskCreated;
use App\Domain\TaskBoard\Events\TaskDeleted;
use App\Domain\TaskBoard\Events\TaskMoved;
use App\Domain\TaskBoard\Events\TaskUpdated;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Services\WebhookDispatcher;
use App\Models\User;

/**
 * Bridges domain events to the WebhookDispatcher so any tenant with
 * matching subscriptions gets an outbound HTTP POST.
 */
class FanoutWebhookEvents
{
    public function __construct(private readonly WebhookDispatcher $hooks) {}

    public function handleTaskCreated(TaskCreated $e): void
    {
        $this->hooks->dispatchTaskEvent('task.created', $e->task, $this->actor($e->actorId), []);
    }

    public function handleTaskMoved(TaskMoved $e): void
    {
        $payload = [
            'from_column_id' => (int) $e->fromColumnId,
            'to_column_id' => (int) $e->toColumnId,
            'completed' => (bool) $e->enteredDoneColumn,
        ];
        $this->hooks->dispatchTaskEvent('task.moved', $e->task, $this->actor($e->actorId), $payload);
        if ($e->enteredDoneColumn) {
            $this->hooks->dispatchTaskEvent('task.completed', $e->task, $this->actor($e->actorId), $payload);
        }
    }

    public function handleTaskUpdated(TaskUpdated $e): void
    {
        $this->hooks->dispatchTaskEvent('task.updated', $e->task, $this->actor($e->actorId), [
            'changed_fields' => $e->dirty ?? [],
        ]);
    }

    public function handleTaskDeleted(TaskDeleted $e): void
    {
        // Reconstruct a minimal Task stub — by the time this fires the row
        // is gone, but the dispatcher only needs tenant_id + board_id and a
        // reference. We pull from the event payload.
        $stub = new Task();
        $stub->setRawAttributes([
            'id' => $e->taskId,
            'board_id' => $e->boardId,
            'tenant_id' => app('tenant.id'),
            'reference' => '#'.$e->taskId,
            'title' => '(deleted)',
        ], true);
        $this->hooks->dispatchTaskEvent('task.deleted', $stub, $this->actor($e->actorId), []);
    }

    public function handleCommentAdded(CommentAdded $e): void
    {
        $task = $e->comment->task;
        if (! $task) return;
        $this->hooks->dispatchTaskEvent('comment.added', $task, $this->actor($e->actorId ?? null), [
            'comment_id' => $e->comment->id,
            'summary' => \Illuminate\Support\Str::limit(strip_tags((string) $e->comment->body), 200),
        ]);
    }

    private function actor(?int $userId): ?User
    {
        if (! $userId) return null;
        return User::find($userId);
    }
}
