<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Observers;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\Task;
use Illuminate\Support\Facades\DB;

class TaskObserver
{
    /**
     * Atomically allocate the next task number for the board on create, and
     * cache a stable reference like "PRD-42" on the row. Locks the board row
     * for update so two simultaneous creates can't claim the same number.
     */
    public function creating(Task $task): void
    {
        if ($task->number) {
            return;
        }

        DB::transaction(function () use ($task) {
            /** @var Board $board */
            $board = Board::query()->withoutGlobalScopes()->whereKey($task->board_id)->lockForUpdate()->firstOrFail();

            $task->number = $board->next_task_number;
            $task->reference = ($board->key ?: 'TASK').'-'.$task->number;

            $board->forceFill(['next_task_number' => $board->next_task_number + 1])->save();
        });

        // Default position: at the bottom of the column if not set.
        if ($task->position === null) {
            $max = Task::query()
                ->withoutGlobalScopes()
                ->where('board_column_id', $task->board_column_id)
                ->max('position');
            $task->position = ($max ?? 0) + 1000.0;
        }
    }

    /**
     * Auto-stamp completed_at when transitioning into a done column, and clear
     * it when transitioning out. Keeps the column source-of-truth for "done"
     * status while letting filters/reports use a real timestamp.
     */
    public function updating(Task $task): void
    {
        if ($task->isDirty('board_column_id')) {
            $newColumn = $task->column()->withoutGlobalScopes()->first();
            if ($newColumn?->is_done && ! $task->completed_at) {
                $task->completed_at = now();
            } elseif ($newColumn && ! $newColumn->is_done && $task->completed_at) {
                $task->completed_at = null;
            }
        }
    }
}
