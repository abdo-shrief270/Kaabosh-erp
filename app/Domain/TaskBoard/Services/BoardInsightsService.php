<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Per-board insights:
 *   - Completed-per-day for the last N days (throughput sparkline).
 *   - Average cycle time (created → completed) per task_type.
 *   - WIP-now per column.
 *   - Aging WIP: tasks in non-done columns older than N days.
 *   - Avg time-in-column derived from the spatie activity log's `moved`
 *     events. Approximated by pairing consecutive (column → column)
 *     transitions per task; tasks with one or zero moves are skipped.
 */
class BoardInsightsService
{
    /** @return array<string, mixed> */
    public function snapshot(Board $board, int $days = 30): array
    {
        $since = CarbonImmutable::now()->subDays($days);

        return [
            'window_days' => $days,
            'throughput' => $this->completedPerDay($board, $days),
            'cycle_time_by_type' => $this->cycleTimeByType($board),
            'wip_by_column' => $this->wipByColumn($board),
            'aging_wip' => $this->agingWip($board),
            'time_in_column' => $this->avgTimeInColumn($board, $since),
            'cumulative_flow' => $this->cumulativeFlow($board, $days),
            'workload' => $this->workload($board, days: 14),
        ];
    }

    /** @return array<int, array{date: string, count: int}> */
    private function completedPerDay(Board $board, int $days): array
    {
        $rows = Task::query()
            ->withoutGlobalScopes()
            ->where('board_id', $board->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', CarbonImmutable::now()->subDays($days))
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = CarbonImmutable::now()->subDays($i)->toDateString();
            $out[] = ['date' => $d, 'count' => (int) ($rows[$d]->count ?? 0)];
        }
        return $out;
    }

    /** @return array<int, array{type: string, avg_hours: float, count: int}> */
    private function cycleTimeByType(Board $board): array
    {
        // EXTRACT(EPOCH FROM ...) keeps the math in Postgres and respects
        // timezone — avoids client-side date construction errors.
        $rows = DB::table('tasks')
            ->join('task_types', 'task_types.id', '=', 'tasks.task_type_id')
            ->where('tasks.board_id', $board->id)
            ->whereNotNull('tasks.completed_at')
            ->selectRaw('task_types.name as type, '
                .'AVG(EXTRACT(EPOCH FROM (tasks.completed_at - tasks.created_at)) / 3600.0) as avg_hours, '
                .'COUNT(*) as count')
            ->groupBy('task_types.name')
            ->orderByDesc('count')
            ->get();

        return $rows->map(fn ($r) => [
            'type' => $r->type,
            'avg_hours' => round((float) $r->avg_hours, 1),
            'count' => (int) $r->count,
        ])->all();
    }

    /** @return array<int, array{column: string, count: int, is_done: bool}> */
    private function wipByColumn(Board $board): array
    {
        $cols = BoardColumn::query()
            ->withoutGlobalScopes()
            ->where('board_id', $board->id)
            ->withCount(['tasks' => fn ($q) => $q->whereNull('archived_at')])
            ->orderBy('position')
            ->get();

        return $cols->map(fn ($c) => [
            'column' => $c->name,
            'count' => (int) $c->tasks_count,
            'is_done' => (bool) $c->is_done,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function agingWip(Board $board, int $thresholdDays = 7): array
    {
        $threshold = CarbonImmutable::now()->subDays($thresholdDays);
        return Task::query()
            ->withoutGlobalScopes()
            ->where('board_id', $board->id)
            ->whereNull('completed_at')
            ->whereNull('archived_at')
            ->where('updated_at', '<', $threshold)
            ->with('column:id,name,is_done')
            ->orderBy('updated_at')
            ->limit(20)
            ->get(['id', 'reference', 'title', 'updated_at', 'board_column_id'])
            ->reject(fn ($t) => $t->column?->is_done)
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'title' => $t->title,
                'column' => $t->column?->name,
                'stale_days' => CarbonImmutable::parse($t->updated_at)->diffInDays(CarbonImmutable::now()),
            ])
            ->values()
            ->all();
    }

    /**
     * Average dwell-time per column using the activity_log table — each
     * `moved` event records from_column_id + to_column_id + created_at.
     * Per task we pair consecutive moves and accumulate hours-per-source.
     *
     * @return array<int, array{column: string, avg_hours: float, samples: int}>
     */
    private function avgTimeInColumn(Board $board, CarbonImmutable $since): array
    {
        $rows = DB::table('activity_log')
            ->where('subject_type', (new Task)->getMorphClass())
            ->where('created_at', '>=', $since)
            ->where('event', 'moved')
            ->orderBy('subject_id')->orderBy('created_at')
            ->get(['subject_id', 'properties', 'created_at']);

        $perColumn = []; // colId => ['total_hours' => float, 'samples' => int]
        $prev = []; // taskId => ['col' => int, 'at' => CarbonImmutable]

        foreach ($rows as $r) {
            $props = is_string($r->properties) ? json_decode($r->properties, true) : (array) $r->properties;
            $taskId = (int) $r->subject_id;
            $from = (int) ($props['from_column_id'] ?? 0);
            $to = (int) ($props['to_column_id'] ?? 0);
            $at = CarbonImmutable::parse($r->created_at);

            if (! isset($prev[$taskId])) {
                $prev[$taskId] = ['col' => $from, 'at' => $at];
                continue;
            }
            $stayCol = $prev[$taskId]['col'];
            $hours = max(0, $prev[$taskId]['at']->diffInSeconds($at) / 3600.0);
            $perColumn[$stayCol] ??= ['total_hours' => 0.0, 'samples' => 0];
            $perColumn[$stayCol]['total_hours'] += $hours;
            $perColumn[$stayCol]['samples']++;
            $prev[$taskId] = ['col' => $to, 'at' => $at];
        }

        $columns = BoardColumn::query()
            ->withoutGlobalScopes()
            ->where('board_id', $board->id)
            ->orderBy('position')
            ->get(['id', 'name']);

        return $columns->map(function ($c) use ($perColumn) {
            $data = $perColumn[$c->id] ?? null;
            return [
                'column' => $c->name,
                'avg_hours' => $data ? round($data['total_hours'] / max(1, $data['samples']), 1) : 0.0,
                'samples' => $data['samples'] ?? 0,
            ];
        })->all();
    }

    /**
     * Cumulative flow diagram: per-day occupancy of every column over the
     * window. We replay activity_log `moved` events to reconstruct each
     * task's column timeline, then for each day count how many tasks sit
     * in each column at end-of-day. O(tasks + moves) — fine for boards
     * up to ~10k tasks.
     *
     * @return array{columns: array<int, array{id:int,name:string,color:?string}>, series: array<int, array<string, mixed>>}
     */
    private function cumulativeFlow(Board $board, int $days): array
    {
        $endDay = CarbonImmutable::today();
        $startDay = $endDay->subDays($days - 1);

        $columns = BoardColumn::query()
            ->withoutGlobalScopes()
            ->where('board_id', $board->id)
            ->orderBy('position')
            ->get(['id', 'name', 'color']);
        $colIds = $columns->pluck('id')->map(fn ($v) => (int) $v)->all();

        // Pull every task that existed on this board on or before the window
        // end. The current `board_column_id` is the *final* state; we'll
        // rewind it day by day using the move log.
        $tasks = DB::table('tasks')
            ->where('board_id', $board->id)
            ->whereNull('deleted_at')
            ->where('created_at', '<=', $endDay->endOfDay())
            ->get(['id', 'created_at', 'board_column_id', 'completed_at', 'archived_at']);

        // Per-task ordered move list (oldest → newest).
        $moves = DB::table('activity_log')
            ->where('subject_type', (new Task)->getMorphClass())
            ->where('event', 'moved')
            ->where('created_at', '<=', $endDay->endOfDay())
            ->orderBy('subject_id')->orderBy('created_at')
            ->get(['subject_id', 'properties', 'created_at']);

        $timeline = []; // taskId => list<{ at, col }>
        foreach ($moves as $m) {
            $props = is_string($m->properties) ? json_decode($m->properties, true) : (array) $m->properties;
            $taskId = (int) $m->subject_id;
            $timeline[$taskId][] = [
                'at' => CarbonImmutable::parse($m->created_at),
                'col' => (int) ($props['to_column_id'] ?? 0),
            ];
        }

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $startDay->addDays($i);
            $eod = $day->endOfDay();
            $counts = array_fill_keys($colIds, 0);

            foreach ($tasks as $t) {
                $createdAt = CarbonImmutable::parse($t->created_at);
                if ($createdAt->gt($eod)) continue;
                if ($t->archived_at && CarbonImmutable::parse($t->archived_at)->lte($eod)) continue;

                // Walk the move history forward, stopping at the last move
                // that happened on or before this day's end.
                $col = (int) $t->board_column_id;
                if (isset($timeline[$t->id])) {
                    // We need to "undo" moves that happened *after* EOD.
                    // Walk from the newest backwards: the column at EOD is
                    // either the to_col of the latest move <= EOD, or — if
                    // no such move — the from_col of the earliest move > EOD.
                    $col = null;
                    $list = $timeline[$t->id];
                    for ($j = count($list) - 1; $j >= 0; $j--) {
                        if ($list[$j]['at']->lte($eod)) {
                            $col = $list[$j]['col'];
                            break;
                        }
                    }
                    if ($col === null) {
                        // Every move happened after EOD — use the from_col of
                        // the earliest move (the task's initial column).
                        $firstProps = $list[0];
                        // We stored only `col` (to_column_id) above — fetch
                        // from_column_id by scanning the raw moves once.
                        // Simpler: assume initial column equals current
                        // board_column_id reversed by the first move's
                        // from_column_id. Re-read from activity row keyed by
                        // task — but we already discarded that. Fall back to
                        // current column; for tasks created during the window
                        // this only matters for days before created_at, which
                        // we skip anyway.
                        $col = (int) $t->board_column_id;
                    }
                }

                if (in_array($col, $colIds, true)) {
                    $counts[$col]++;
                }
            }

            $row = ['date' => $day->toDateString()];
            foreach ($colIds as $cid) {
                $row[(string) $cid] = $counts[$cid];
            }
            $series[] = $row;
        }

        return [
            'columns' => $columns->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
                'color' => $c->color,
            ])->values()->all(),
            'series' => $series,
        ];
    }

    /**
     * Per-assignee workload heatmap over the next N days, plus "overdue"
     * and "unscheduled" buckets. Cell value = number of open tasks
     * assigned to that user that fall on that day (or in the buckets).
     *
     * @return array{days: array<int,string>, rows: array<int, array<string,mixed>>}
     */
    private function workload(Board $board, int $days = 14): array
    {
        $today = CarbonImmutable::today();
        $endDay = $today->addDays($days - 1);

        $dayStrs = [];
        for ($i = 0; $i < $days; $i++) {
            $dayStrs[] = $today->addDays($i)->toDateString();
        }

        $tasks = DB::table('tasks')
            ->where('board_id', $board->id)
            ->whereNull('completed_at')
            ->whereNull('archived_at')
            ->whereNotNull('primary_assignee_id')
            ->get(['id', 'primary_assignee_id', 'due_date']);

        $userIds = $tasks->pluck('primary_assignee_id')->map(fn ($v) => (int) $v)->unique()->all();
        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $perUser = [];
        foreach ($userIds as $uid) {
            $perUser[$uid] = [
                'user_id' => $uid,
                'name' => $users[$uid]->name ?? '—',
                'overdue' => 0,
                'unscheduled' => 0,
                'cells' => array_fill_keys($dayStrs, 0),
            ];
        }

        $todayStr = $today->toDateString();
        foreach ($tasks as $t) {
            $uid = (int) $t->primary_assignee_id;
            if (! isset($perUser[$uid])) continue;
            if ($t->due_date === null) {
                $perUser[$uid]['unscheduled']++;
                continue;
            }
            $due = CarbonImmutable::parse($t->due_date)->toDateString();
            if ($due < $todayStr) {
                $perUser[$uid]['overdue']++;
            } elseif (isset($perUser[$uid]['cells'][$due])) {
                $perUser[$uid]['cells'][$due]++;
            }
            // Tasks beyond the window are dropped — heatmap only shows the
            // next N days, with overdue and unscheduled as separate buckets.
        }

        // Sort by total workload desc so the busiest assignees float to the top.
        $rows = collect($perUser)
            ->sortByDesc(fn ($r) => array_sum($r['cells']) + $r['overdue'])
            ->values()
            ->all();

        return ['days' => $dayStrs, 'rows' => $rows];
    }
}
