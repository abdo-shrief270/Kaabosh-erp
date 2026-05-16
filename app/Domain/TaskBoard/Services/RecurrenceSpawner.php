<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Enums\RecurrenceFrequency;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskRecurrence;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Spawns concrete tasks from a recurrence template when their next_spawn_at
 * has elapsed. Hourly cron drives this; the algorithm:
 *
 *   1. SELECT … WHERE is_active AND next_spawn_at <= now
 *      AND (ends_at IS NULL OR ends_at >= now)
 *      AND (max_occurrences IS NULL OR spawned_count < max_occurrences).
 *   2. For each row: create a Task from the template (title, description,
 *      priority, default assignee + tags), advance next_spawn_at to the
 *      next occurrence after now, bump spawned_count.
 *   3. If we've hit the end conditions (ends_at passed, or
 *      spawned_count == max_occurrences after the increment), flip the
 *      recurrence inactive.
 *
 * Catch-up: if the cron skipped runs and several occurrences are due, we
 * deliberately spawn at most one task per recurrence per run — avoids
 * unleashing a flood after downtime and keeps each spawn audit-traceable.
 */
class RecurrenceSpawner
{
    public function __construct(private readonly TaskAssigneeService $assignees) {}

    /**
     * Spawn all due recurrences. Returns count of tasks created.
     */
    public function spawnDue(?CarbonImmutable $now = null): int
    {
        $now = $now ?? CarbonImmutable::now();
        $created = 0;

        TaskRecurrence::query()
            ->withoutGlobalScopes()
            ->whereIn('id', function ($q) use ($now) {
                $q->select('id')->from('task_recurrences')
                    ->where('is_active', true)
                    ->where('next_spawn_at', '<=', $now)
                    ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                    ->where(fn ($q) => $q->whereNull('max_occurrences')->orWhereColumn('spawned_count', '<', 'max_occurrences'));
            })
            ->orderBy('id')
            ->chunkById(100, function ($batch) use (&$created, $now): void {
                foreach ($batch as $recurrence) {
                    try {
                        if ($this->spawnOne($recurrence, $now)) {
                            $created++;
                        }
                    } catch (\Throwable $e) {
                        Log::error('Recurrence spawn failed', [
                            'recurrence_id' => $recurrence->id,
                            'tenant_id' => $recurrence->tenant_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $created;
    }

    public function spawnOne(TaskRecurrence $recurrence, CarbonImmutable $now): bool
    {
        return DB::transaction(function () use ($recurrence, $now) {
            /** @var TaskRecurrence $r */
            $r = TaskRecurrence::query()
                ->withoutGlobalScopes()
                ->whereKey($recurrence->id)
                ->lockForUpdate()
                ->first();

            if (! $r || ! $r->is_active || $r->next_spawn_at?->isAfter($now)) {
                return false;
            }

            // Bind tenant so BelongsToTenant fills it on create.
            app()->instance('tenant.id', $r->tenant_id);

            /** @var Task $task */
            $task = Task::query()->withoutGlobalScopes()->create([
                'tenant_id' => $r->tenant_id,
                'board_id' => $r->board_id,
                'board_column_id' => $r->board_column_id,
                'task_type_id' => $r->task_type_id,
                'title' => $r->title,
                'description' => $r->description,
                'priority' => $r->priority?->value ?? 'medium',
                'reporter_id' => $r->created_by,
                'primary_assignee_id' => $r->default_assignee_id,
            ]);

            if ($r->default_assignee_id) {
                $this->assignees->syncAssignees($task, [$r->default_assignee_id], $r->created_by);
            }
            if ($r->default_tag_ids) {
                $task->tags()->sync($r->default_tag_ids);
            }

            // Advance the cursor.
            $nextSpawn = $this->nextOccurrence($r, $now);
            $r->forceFill([
                'last_spawned_at' => $now,
                'spawned_count' => $r->spawned_count + 1,
                'next_spawn_at' => $nextSpawn,
                'is_active' => $this->shouldRemainActive($r, $nextSpawn),
            ])->save();

            return true;
        });
    }

    /**
     * Compute the first occurrence after `$now` using the recurrence's
     * frequency/interval/byday/cron fields. Returns null when the schedule
     * is exhausted (ends_at exceeded or max_occurrences reached).
     */
    public function nextOccurrence(TaskRecurrence $r, CarbonImmutable $reference): ?CarbonImmutable
    {
        $tz = $r->timezone ?: 'UTC';
        $ref = $reference->setTimezone($tz);

        $candidate = match ($r->frequency) {
            RecurrenceFrequency::Daily => $ref->addDays($r->interval),
            RecurrenceFrequency::Weekly => $this->nextWeekly($r, $ref),
            RecurrenceFrequency::Monthly => $this->nextMonthly($r, $ref),
            RecurrenceFrequency::Yearly => $ref->addYears($r->interval),
            RecurrenceFrequency::Cron => $this->nextCron($r, $ref),
        };

        if (! $candidate) {
            return null;
        }

        if ($r->ends_at && $candidate->isAfter($r->ends_at)) {
            return null;
        }
        if ($r->max_occurrences && $r->spawned_count + 1 >= $r->max_occurrences) {
            return null;
        }

        return $candidate->utc();
    }

    private function nextWeekly(TaskRecurrence $r, CarbonImmutable $ref): CarbonImmutable
    {
        $byday = collect($r->byday ?? [])->map(fn ($d) => (int) $d)->filter()->values();
        if ($byday->isEmpty()) {
            return $ref->addWeeks($r->interval);
        }
        // Find the next day-of-week in $byday (1=Mon … 7=Sun) after ref.
        $current = $ref->dayOfWeekIso;
        $sorted = $byday->sort()->values();
        $next = $sorted->first(fn ($d) => $d > $current) ?? null;
        if ($next !== null) {
            return $ref->addDays($next - $current);
        }
        // Wrap to next week interval.
        return $ref->addDays((7 * $r->interval) - $current + (int) $sorted->first());
    }

    private function nextMonthly(TaskRecurrence $r, CarbonImmutable $ref): CarbonImmutable
    {
        $daysOfMonth = collect($r->byday ?? [])->map(fn ($d) => (int) $d)->filter()->sort()->values();
        if ($daysOfMonth->isEmpty()) {
            return $ref->addMonthsNoOverflow($r->interval);
        }
        $next = $daysOfMonth->first(fn ($d) => $d > $ref->day);
        if ($next !== null) {
            return $ref->setDay($next);
        }

        return $ref->addMonthsNoOverflow($r->interval)->setDay((int) $daysOfMonth->first());
    }

    private function nextCron(TaskRecurrence $r, CarbonImmutable $ref): ?CarbonImmutable
    {
        if (! $r->cron_expression) {
            return null;
        }
        try {
            $cron = new CronExpression($r->cron_expression);
            $next = $cron->getNextRunDate($ref->toDateTime(), 0, false, $ref->timezoneName);

            return CarbonImmutable::instance($next);
        } catch (\Throwable) {
            return null;
        }
    }

    private function shouldRemainActive(TaskRecurrence $r, ?CarbonImmutable $next): bool
    {
        if (! $next) {
            return false;
        }
        if ($r->ends_at && $next->isAfter($r->ends_at)) {
            return false;
        }
        if ($r->max_occurrences && $r->spawned_count >= $r->max_occurrences) {
            return false;
        }

        return true;
    }
}
