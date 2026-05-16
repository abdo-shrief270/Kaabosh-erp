<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Float-position ordering for kanban columns + tasks.
 *
 * Positions are doubles (default gap 1000) so inserts between neighbours are
 * O(1) — set position to (prev + next) / 2. When precision degrades (the gap
 * between two siblings shrinks below 1.0), `rebalanceColumn` reseeds the
 * column to 1000-step gaps. Pure midpoints can still produce ~1e-300 gaps
 * after enough inserts, so we proactively rebalance when we detect it.
 */
class TaskMovementService
{
    private const GAP = 1000.0;
    private const REBALANCE_THRESHOLD = 1.0;

    /**
     * Move a task to a destination column, anchored either by neighbour
     * task id (before/after) or by a numeric index. Returns the moved task.
     *
     * @param  array{column_id:int, before_task_id?:int|null, after_task_id?:int|null, index?:int|null}  $target
     */
    public function moveTask(Task $task, array $target): Task
    {
        return DB::transaction(function () use ($task, $target) {
            /** @var BoardColumn $column */
            $column = BoardColumn::query()->whereKey($target['column_id'])->lockForUpdate()->firstOrFail();

            // Cross-board moves are handled by moveTaskCrossBoard() — same
            // service, more invariants. Reject the in-column path so callers
            // pick the right entrypoint deliberately.
            if ($column->board_id !== $task->board_id) {
                throw new InvalidArgumentException('Cannot move task across boards. Use moveTaskCrossBoard().');
            }

            // Workflow constraints: if this board has any transition rules
            // defined for the task's type, the requested edge must be in
            // the allow-list. Same-column moves (reorder) are always OK.
            if ($task->task_type_id && $task->board_column_id !== $column->id) {
                $hasRules = \App\Domain\TaskBoard\Models\TaskWorkflowTransition::query()
                    ->where('board_id', $task->board_id)
                    ->where('task_type_id', $task->task_type_id)
                    ->exists();
                if ($hasRules) {
                    $allowed = \App\Domain\TaskBoard\Models\TaskWorkflowTransition::query()
                        ->where('board_id', $task->board_id)
                        ->where('task_type_id', $task->task_type_id)
                        ->where('from_column_id', $task->board_column_id)
                        ->where('to_column_id', $column->id)
                        ->exists();
                    if (! $allowed) {
                        abort(response()->json([
                            'error' => 'transition_not_allowed',
                            'message' => "This task type can't move from its current column to '{$column->name}'.",
                            'from_column_id' => (int) $task->board_column_id,
                            'to_column_id' => $column->id,
                            'task_type_id' => (int) $task->task_type_id,
                        ], 422));
                    }
                }
            }

            // Approval gate: if the destination column requires approval
            // AND we're crossing into it (not reordering within it),
            // refuse the move and signal an approval-required state. The
            // controller turns this into a 202 with an approval request.
            if (
                $column->requires_approval
                && $task->board_column_id !== $column->id
                && ! ($target['skip_approval'] ?? false)
            ) {
                abort(response()->json([
                    'error' => 'approval_required',
                    'message' => "Moves into '{$column->name}' require approval.",
                    'from_column_id' => (int) $task->board_column_id,
                    'target_column_id' => $column->id,
                ], 422));
            }

            // Hard WIP enforcement: when a column opted into enforce_wip and
            // the move would push its count over wip_limit, reject. Moves
            // *within* the same column always pass (count is unchanged).
            if (
                $column->enforce_wip
                && $column->wip_limit
                && $task->board_column_id !== $column->id
            ) {
                $current = Task::query()
                    ->where('board_column_id', $column->id)
                    ->whereNull('archived_at')
                    ->whereNull('completed_at')
                    ->count();
                if ($current >= (int) $column->wip_limit) {
                    abort(response()->json([
                        'error' => 'wip_limit_exceeded',
                        'message' => "Column '{$column->name}' is at its WIP limit of {$column->wip_limit}.",
                        'column_id' => $column->id,
                        'wip_limit' => (int) $column->wip_limit,
                    ], 422));
                }
            }

            $newPos = $this->resolvePosition(
                columnId: $column->id,
                excludeTaskId: $task->id,
                beforeTaskId: $target['before_task_id'] ?? null,
                afterTaskId: $target['after_task_id'] ?? null,
                index: $target['index'] ?? null,
            );

            $task->forceFill([
                'board_column_id' => $column->id,
                'position' => $newPos,
            ])->save();

            // Trigger observer's column-transition side-effects (completed_at).
            $task->refresh();

            $this->maybeRebalance($column->id);

            return $task;
        });
    }

    /**
     * Move a task (and its subtree) to a column on a DIFFERENT board.
     * Heavier than an in-board move: every task in the subtree drops
     * what doesn't carry across boards (sprint/tag/version memberships,
     * custom field values, dependencies that point off-board), and
     * gets a fresh reference under the destination board's key.
     *
     * The root task lands in the target column. Subtasks keep their
     * parent_task_id pointing at the moved parent — but their column
     * is reset to the destination board's initial column (since the
     * old column doesn't exist on the new board).
     *
     * Returns the root task fresh from DB so callers see the new
     * reference.
     */
    public function moveTaskCrossBoard(Task $task, BoardColumn $targetColumn): Task
    {
        if ($targetColumn->board_id === $task->board_id) {
            throw new InvalidArgumentException('Use moveTask() for same-board moves.');
        }
        if ((int) $targetColumn->tenant_id !== (int) $task->tenant_id) {
            throw new InvalidArgumentException('Cross-tenant moves are not allowed.');
        }

        return DB::transaction(function () use ($task, $targetColumn) {
            /** @var \App\Domain\TaskBoard\Models\Board $newBoard */
            $newBoard = \App\Domain\TaskBoard\Models\Board::query()
                ->whereKey($targetColumn->board_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Collect the whole subtree breadth-first so children land in
            // a stable order. We cap recursion depth to 12 — deeper trees
            // suggest a cycle (which the model layer shouldn't produce
            // but a defensive cap is cheap).
            $subtree = $this->collectSubtree((int) $task->id, maxDepth: 12);
            $subtreeIds = array_map(fn ($t) => (int) $t->id, $subtree);

            // Reset board-scoped membership for every task in the subtree.
            DB::table('sprint_task')->whereIn('task_id', $subtreeIds)->delete();
            DB::table('task_tag')->whereIn('task_id', $subtreeIds)->delete();
            DB::table('task_version')->whereIn('task_id', $subtreeIds)->delete();
            DB::table('task_custom_field_values')->whereIn('task_id', $subtreeIds)->delete();
            // Drop dependencies that would dangle across the boundary:
            // keep edges where BOTH endpoints are in the moving subtree;
            // delete the rest.
            DB::table('task_dependencies')
                ->where(function ($q) use ($subtreeIds) {
                    $q->whereIn('task_id', $subtreeIds)
                        ->orWhereIn('depends_on_task_id', $subtreeIds);
                })
                ->whereRaw('(task_id NOT IN ('.implode(',', $subtreeIds ?: [0]).') OR depends_on_task_id NOT IN ('.implode(',', $subtreeIds ?: [0]).'))')
                ->delete();

            // Pick a destination column for non-root subtasks: the new
            // board's initial column (or its first column if none is
            // marked as initial).
            $childColumnId = (int) (BoardColumn::query()
                ->where('board_id', $newBoard->id)
                ->where('is_initial', true)
                ->value('id')
                ?? BoardColumn::query()
                    ->where('board_id', $newBoard->id)
                    ->orderBy('position')
                    ->value('id'));

            $rootEndPosition = ((float) Task::query()
                ->where('board_column_id', $targetColumn->id)
                ->max('position')) + self::GAP;
            $childEndPosition = ((float) Task::query()
                ->where('board_column_id', $childColumnId)
                ->max('position')) + self::GAP;

            // Burn N consecutive reference numbers in one go — locks the
            // board row once, increments next_task_number by N.
            $count = count($subtree);
            $startNumber = (int) $newBoard->next_task_number;

            foreach (array_values($subtree) as $i => $t) {
                $isRoot = (int) $t->id === (int) $task->id;
                $columnId = $isRoot ? (int) $targetColumn->id : $childColumnId;
                $position = $isRoot ? $rootEndPosition : ($childEndPosition + ($i * self::GAP));
                $newNumber = $startNumber + $i;

                // task_type_id is tenant-scoped (not board-scoped), so it
                // travels intact. Workflow rules attach to (board, type)
                // and simply won't apply on the new board until rules are
                // explicitly defined there.
                $t->forceFill([
                    'board_id' => $newBoard->id,
                    'board_column_id' => $columnId,
                    'position' => $position,
                    'number' => $newNumber,
                    'reference' => ($newBoard->key ?: 'TASK').'-'.$newNumber,
                ])->save();
            }

            $newBoard->forceFill(['next_task_number' => $startNumber + $count])->save();

            return $task->fresh();
        });
    }

    /**
     * Breadth-first walk of a task subtree, capped at $maxDepth to defend
     * against accidental cycles. Returns Task models with their original
     * state so we can mutate + save individually.
     *
     * @return array<int, Task>
     */
    private function collectSubtree(int $rootId, int $maxDepth = 12): array
    {
        $out = [];
        $frontier = [$rootId];
        $depth = 0;
        while ($frontier && $depth <= $maxDepth) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Task> $levelTasks */
            $levelTasks = Task::query()->whereIn('id', $frontier)->lockForUpdate()->get();
            foreach ($levelTasks as $t) {
                $out[(int) $t->id] = $t;
            }
            $frontier = Task::query()
                ->whereIn('parent_task_id', $frontier)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
            $depth++;
        }
        return $out;
    }

    /**
     * Reorder all columns of a board. `$orderedIds` must contain every column
     * id on the board; missing ids are rejected to avoid silent drops.
     *
     * @param  int[]  $orderedIds
     */
    public function reorderColumns(Board $board, array $orderedIds): Collection
    {
        return DB::transaction(function () use ($board, $orderedIds) {
            $current = BoardColumn::query()
                ->where('board_id', $board->id)
                ->orderBy('position')
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            $given = array_values(array_unique(array_map('intval', $orderedIds)));
            sort($current);
            $compare = $given;
            sort($compare);

            if ($compare !== $current) {
                throw new InvalidArgumentException('Reorder payload must include every column id on the board.');
            }

            foreach ($given as $i => $id) {
                BoardColumn::query()->whereKey($id)->update(['position' => ($i + 1) * self::GAP]);
            }

            return BoardColumn::query()->where('board_id', $board->id)->orderBy('position')->get();
        });
    }

    /**
     * Reseed every task's position in a column to evenly spaced 1000-step
     * gaps. Idempotent; safe to call anytime.
     */
    public function rebalanceColumn(int $columnId): void
    {
        DB::transaction(function () use ($columnId) {
            $ids = Task::query()
                ->where('board_column_id', $columnId)
                ->orderBy('position')->orderBy('id')
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            foreach ($ids as $i => $id) {
                Task::query()->whereKey($id)->update(['position' => ($i + 1) * self::GAP]);
            }
        });
    }

    private function resolvePosition(int $columnId, int $excludeTaskId, ?int $beforeTaskId, ?int $afterTaskId, ?int $index): float
    {
        // Explicit neighbour anchoring takes priority.
        if ($beforeTaskId) {
            $before = Task::query()->whereKey($beforeTaskId)->value('position');
            $prev = Task::query()
                ->where('board_column_id', $columnId)
                ->where('id', '!=', $excludeTaskId)
                ->where('position', '<', $before)
                ->orderByDesc('position')
                ->value('position');

            return $prev !== null ? ($prev + $before) / 2 : $before - self::GAP;
        }

        if ($afterTaskId) {
            $after = Task::query()->whereKey($afterTaskId)->value('position');
            $next = Task::query()
                ->where('board_column_id', $columnId)
                ->where('id', '!=', $excludeTaskId)
                ->where('position', '>', $after)
                ->orderBy('position')
                ->value('position');

            return $next !== null ? ($after + $next) / 2 : $after + self::GAP;
        }

        if ($index !== null) {
            $positions = Task::query()
                ->where('board_column_id', $columnId)
                ->where('id', '!=', $excludeTaskId)
                ->orderBy('position')
                ->pluck('position')
                ->all();

            $clamped = max(0, min($index, count($positions)));
            if ($clamped === 0) {
                return $positions ? $positions[0] - self::GAP : self::GAP;
            }
            if ($clamped === count($positions)) {
                return end($positions) + self::GAP;
            }
            return ($positions[$clamped - 1] + $positions[$clamped]) / 2;
        }

        // No anchor — append.
        $max = (float) Task::query()->where('board_column_id', $columnId)->max('position');

        return $max + self::GAP;
    }

    private function maybeRebalance(int $columnId): void
    {
        $positions = Task::query()
            ->where('board_column_id', $columnId)
            ->orderBy('position')
            ->pluck('position')
            ->all();

        for ($i = 1, $n = count($positions); $i < $n; $i++) {
            if (($positions[$i] - $positions[$i - 1]) < self::REBALANCE_THRESHOLD) {
                $this->rebalanceColumn($columnId);

                return;
            }
        }
    }
}
