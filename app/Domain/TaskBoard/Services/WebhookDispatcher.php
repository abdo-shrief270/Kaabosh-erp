<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskBoardWebhook;
use App\Jobs\SendTaskBoardWebhookJob;
use App\Models\User;

/**
 * Resolves which webhooks subscribed to `$eventKey` should fire for a
 * given board, and queues a delivery job per match. Subscriptions with
 * NULL board_id are tenant-wide and fire for every board's events.
 */
class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchTaskEvent(string $eventKey, Task $task, ?User $actor, array $payload = []): void
    {
        $hooks = TaskBoardWebhook::query()
            ->where('tenant_id', $task->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) use ($task) {
                $q->whereNull('board_id')->orWhere('board_id', $task->board_id);
            })
            ->get();

        if ($hooks->isEmpty()) return;

        $envelope = array_merge([
            'summary' => $task->title,
            'task' => [
                'id' => $task->id,
                'reference' => $task->reference,
                'title' => $task->title,
                'board_id' => $task->board_id,
                'board_column_id' => $task->board_column_id,
                'url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/task-board/'.$task->board_id.'?task='.$task->id,
            ],
            'actor' => $actor ? ['id' => $actor->id, 'name' => $actor->name] : null,
        ], $payload);

        foreach ($hooks as $hook) {
            $events = $hook->events ?? [];
            if (! in_array($eventKey, $events, true)) continue;
            SendTaskBoardWebhookJob::dispatch((int) $hook->id, $eventKey, $envelope);
        }
    }
}
