<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskTimeEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Per-user "currently running" task timer + start/stop bookkeeping.
 *
 * Invariant: a user has at most one running entry. Starting a timer auto-
 * stops any other running entry the user owns (matches Toggl semantics —
 * you never have two clocks running at once).
 */
class TaskTimerService
{
    public function currentFor(int $userId): ?TaskTimeEntry
    {
        return TaskTimeEntry::query()->where('user_id', $userId)->whereNull('stopped_at')->first();
    }

    public function start(Task $task, int $userId, ?string $description = null): TaskTimeEntry
    {
        return DB::transaction(function () use ($task, $userId, $description) {
            // Auto-stop any other running timer this user owns. Counted as a
            // legitimate session boundary (we cache duration on stop).
            TaskTimeEntry::query()
                ->where('user_id', $userId)
                ->whereNull('stopped_at')
                ->each(fn (TaskTimeEntry $e) => $this->stopEntry($e));

            /** @var TaskTimeEntry $entry */
            $entry = TaskTimeEntry::create([
                'tenant_id' => $task->tenant_id,
                'task_id' => $task->id,
                'user_id' => $userId,
                'started_at' => now(),
                'description' => $description,
            ]);

            return $entry;
        });
    }

    public function stop(int $userId): ?TaskTimeEntry
    {
        $entry = $this->currentFor($userId);
        if (! $entry) {
            return null;
        }
        $this->stopEntry($entry);

        return $entry->fresh();
    }

    /**
     * Roll up logged_hours back onto the task so KanbanCard / list view can
     * show progress vs estimate without joining task_time_entries on every
     * read. Called after each stop.
     */
    public function rollupOnto(Task $task): void
    {
        $totalSeconds = (int) TaskTimeEntry::query()
            ->where('task_id', $task->id)
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');

        $task->forceFill(['logged_hours' => round($totalSeconds / 3600, 2)])->save();
    }

    public function manualLog(Task $task, int $userId, int $minutes, ?string $description = null): TaskTimeEntry
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException('minutes must be > 0');
        }
        $now = now();
        /** @var TaskTimeEntry $entry */
        $entry = TaskTimeEntry::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $userId,
            'started_at' => $now->copy()->subMinutes($minutes),
            'stopped_at' => $now,
            'duration_seconds' => $minutes * 60,
            'description' => $description,
        ]);
        $this->rollupOnto($task);

        return $entry;
    }

    private function stopEntry(TaskTimeEntry $entry): void
    {
        $now = now();
        $entry->forceFill([
            'stopped_at' => $now,
            'duration_seconds' => $now->diffInSeconds($entry->started_at),
        ])->save();
        $this->rollupOnto($entry->task);
    }
}
