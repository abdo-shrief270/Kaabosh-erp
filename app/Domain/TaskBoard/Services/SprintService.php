<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Enums\SprintStatus;
use App\Domain\TaskBoard\Models\Sprint;
use App\Domain\TaskBoard\Models\SprintBurndownSnapshot;
use App\Domain\TaskBoard\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sprint lifecycle + invariants.
 *
 * One active sprint per board — enforced application-side because Postgres
 * partial-unique on a derived state column is brittle to migrate. Transition
 * rules:
 *   planned   → active       (start, freezes committed metrics)
 *   active    → completed    (complete, optionally rolls incomplete tasks → next)
 *   *         → cancelled    (admin override)
 *
 * Daily burndown snapshot is best-effort; called by the scheduler. Re-running
 * for the same date overwrites — idempotent.
 */
class SprintService
{
    public function start(Sprint $sprint): Sprint
    {
        if ($sprint->status !== SprintStatus::Planned) {
            throw new RuntimeException('Only planned sprints can be started.');
        }
        return DB::transaction(function () use ($sprint) {
            $active = Sprint::query()
                ->where('board_id', $sprint->board_id)
                ->where('status', SprintStatus::Active->value)
                ->lockForUpdate()
                ->first();
            if ($active) {
                throw new RuntimeException('Another sprint is already active on this board.');
            }

            // Freeze committed metrics.
            $tasks = $sprint->tasks()->get(['tasks.id', 'tasks.estimate_hours']);
            $sprint->forceFill([
                'status' => SprintStatus::Active,
                'started_at' => now(),
                'committed_task_count' => $tasks->count(),
                'committed_estimate_hours' => (float) $tasks->sum('estimate_hours'),
            ])->save();

            $this->snapshot($sprint->fresh());

            return $sprint->fresh();
        });
    }

    /**
     * Complete the sprint. When $rolloverSprintId is given, incomplete tasks
     * are moved into that sprint (must be planned, same board).
     */
    public function complete(Sprint $sprint, ?int $rolloverSprintId = null): Sprint
    {
        if ($sprint->status !== SprintStatus::Active) {
            throw new RuntimeException('Only active sprints can be completed.');
        }
        return DB::transaction(function () use ($sprint, $rolloverSprintId) {
            $sprint->forceFill([
                'status' => SprintStatus::Completed,
                'completed_at' => now(),
            ])->save();

            if ($rolloverSprintId) {
                /** @var Sprint $target */
                $target = Sprint::query()
                    ->where('board_id', $sprint->board_id)
                    ->where('status', SprintStatus::Planned->value)
                    ->whereKey($rolloverSprintId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $incomplete = $sprint->tasks()->whereNull('tasks.completed_at')->pluck('tasks.id');
                if ($incomplete->isNotEmpty()) {
                    $now = now();
                    $sprint->tasks()->detach($incomplete);
                    foreach ($incomplete as $taskId) {
                        $target->tasks()->syncWithoutDetaching([
                            $taskId => ['added_by_id' => request()->user()?->id, 'added_at' => $now],
                        ]);
                    }
                }
            }

            $this->snapshot($sprint->fresh());

            return $sprint->fresh();
        });
    }

    public function cancel(Sprint $sprint): Sprint
    {
        $sprint->forceFill([
            'status' => SprintStatus::Cancelled,
            'completed_at' => $sprint->completed_at ?? now(),
        ])->save();

        return $sprint->fresh();
    }

    /**
     * Take a daily snapshot for the chart. Idempotent on date — last-write
     * wins so re-runs are safe.
     */
    public function snapshot(Sprint $sprint, ?CarbonImmutable $when = null): SprintBurndownSnapshot
    {
        $when = $when ?? CarbonImmutable::now();
        $today = $when->toDateString();

        $rows = $sprint->tasks()->get(['tasks.id', 'tasks.estimate_hours', 'tasks.completed_at']);
        $remaining = $rows->whereNull('completed_at');
        $completed = $rows->count() - $remaining->count();

        return SprintBurndownSnapshot::updateOrCreate(
            ['sprint_id' => $sprint->id, 'snapshot_date' => $today],
            [
                'remaining_estimate_hours' => (float) $remaining->sum('estimate_hours'),
                'remaining_task_count' => $remaining->count(),
                'completed_task_count' => $completed,
            ],
        );
    }

    /**
     * Stats for the SprintsBar — current sprint progress and days-left at a
     * glance. Computed live (no cache) because the inputs are small.
     *
     * @return array<string, mixed>
     */
    public function activeStats(int $boardId): ?array
    {
        /** @var Sprint|null $sprint */
        $sprint = Sprint::query()
            ->where('board_id', $boardId)
            ->where('status', SprintStatus::Active->value)
            ->first();
        if (! $sprint) {
            return null;
        }

        $rows = $sprint->tasks()->get(['tasks.id', 'tasks.estimate_hours', 'tasks.completed_at']);
        $total = $rows->count();
        $done = $rows->whereNotNull('completed_at')->count();
        $est = (float) $rows->sum('estimate_hours');
        $estDone = (float) $rows->whereNotNull('completed_at')->sum('estimate_hours');

        $now = CarbonImmutable::now();
        $end = CarbonImmutable::parse($sprint->ends_at);
        $start = CarbonImmutable::parse($sprint->starts_at);
        $totalDays = max(1, $start->diffInDays($end));
        $daysLeft = max(0, $now->diffInDays($end, false));
        $daysElapsed = max(0, $totalDays - $daysLeft);

        return [
            'sprint' => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'starts_at' => $sprint->starts_at,
                'ends_at' => $sprint->ends_at,
                'status' => $sprint->status->value,
            ],
            'tasks' => ['total' => $total, 'done' => $done],
            'estimate' => ['total' => $est, 'done' => $estDone],
            'days' => ['total' => $totalDays, 'elapsed' => $daysElapsed, 'left' => $daysLeft],
        ];
    }
}
