<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Enums\AutomationTrigger;
use App\Domain\TaskBoard\Models\AutomationRule;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evaluates automation rules for a given event. Pipeline per rule:
 *   1. Filter by trigger_type and trigger_config match.
 *   2. Check every condition (AND); skip if any fails.
 *   3. Execute every action; if any throws, the rule's error_count goes up
 *      but other rules keep firing.
 *
 * Rule firings are tracked on the rule itself (last_fired_at, fire_count)
 * — sufficient for a v1 audit. Per-fire log rows can come later if we need
 * them for debugging.
 */
class AutomationRuleEvaluator
{
    public function __construct(
        private readonly TaskMovementService $movement,
    ) {}

    /**
     * Evaluate all matching active rules for the trigger + task.
     *
     * @param  array<string, mixed>  $eventPayload  trigger-type specific extras
     */
    public function evaluate(AutomationTrigger $trigger, Task $task, array $eventPayload = [], ?int $actorId = null): void
    {
        $rules = AutomationRule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $task->tenant_id)
            ->where('is_active', true)
            ->where('trigger_type', $trigger->value)
            ->where(fn ($q) => $q->where('board_id', $task->board_id)->orWhereNull('board_id'))
            ->get();

        foreach ($rules as $rule) {
            if (! $this->triggerMatches($rule, $eventPayload)) {
                continue;
            }
            if (! $this->conditionsMatch($rule, $task)) {
                continue;
            }
            $this->runActions($rule, $task, $actorId);
        }
    }

    /**
     * Match the trigger_config against the live event. Only fields present
     * in trigger_config are checked — empty config matches anything of that
     * trigger type.
     *
     * @param  array<string, mixed>  $eventPayload
     */
    private function triggerMatches(AutomationRule $rule, array $eventPayload): bool
    {
        $config = (array) ($rule->trigger_config ?? []);

        foreach ($config as $key => $expected) {
            if ($expected === null || $expected === '') continue;
            $actual = $eventPayload[$key] ?? null;
            if ((string) $actual !== (string) $expected) {
                return false;
            }
        }

        return true;
    }

    private function conditionsMatch(AutomationRule $rule, Task $task): bool
    {
        $conditions = (array) ($rule->conditions ?? []);

        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? null;
            $op = $cond['op'] ?? 'is';
            $value = $cond['value'] ?? null;

            $actual = match ($field) {
                'priority' => $task->priority?->value,
                'task_type_id' => $task->task_type_id,
                'tag_id' => $task->tags()->pluck('tags.id')->all(),
                'assignee_id' => $task->assignees()->pluck('users.id')->all(),
                'primary_assignee_id' => $task->primary_assignee_id,
                default => null,
            };

            if (! $this->matchOp($op, $actual, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  mixed  $actual
     * @param  mixed  $expected
     */
    private function matchOp(string $op, $actual, $expected): bool
    {
        return match ($op) {
            'is' => (string) $actual === (string) $expected,
            'not' => (string) $actual !== (string) $expected,
            'in' => is_array($expected) && in_array($actual, $expected, false),
            'not_in' => is_array($expected) && ! in_array($actual, $expected, false),
            'has' => is_array($actual) && in_array($expected, $actual, false),
            'has_not' => is_array($actual) && ! in_array($expected, $actual, false),
            default => false,
        };
    }

    private function runActions(AutomationRule $rule, Task $task, ?int $actorId): void
    {
        $actions = (array) ($rule->actions ?? []);
        $error = null;

        DB::transaction(function () use ($actions, $task, $actorId, &$error) {
            foreach ($actions as $action) {
                try {
                    $this->runOne($action, $task, $actorId);
                } catch (\Throwable $e) {
                    $error = $error ?? $e->getMessage();
                    Log::warning('Automation action failed', [
                        'action' => $action,
                        'task_id' => $task->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $rule->forceFill([
            'last_fired_at' => now(),
            'fire_count' => $rule->fire_count + 1,
            'error_count' => $rule->error_count + ($error ? 1 : 0),
            'last_error' => $error,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function runOne(array $action, Task $task, ?int $actorId): void
    {
        $type = $action['type'] ?? null;
        $payload = (array) ($action['payload'] ?? []);

        match ($type) {
            'move_to_column' => $this->movement->moveTask($task, ['column_id' => (int) $payload['column_id']]),
            'assign_to' => $this->doAssign($task, $payload, $actorId),
            'add_tag' => $task->tags()->syncWithoutDetaching([(int) $payload['tag_id']]),
            'remove_tag' => $task->tags()->detach((int) $payload['tag_id']),
            'set_priority' => $task->forceFill(['priority' => (string) $payload['priority']])->save(),
            'post_comment' => $this->doComment($task, $payload, $actorId),
            'add_to_sprint' => $this->doAddToSprint($task, $payload, $actorId),
            default => throw new \RuntimeException("Unknown action type: {$type}"),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function doAssign(Task $task, array $payload, ?int $actorId): void
    {
        $userId = (int) $payload['user_id'];
        $mode = (string) ($payload['mode'] ?? 'add');
        if ($mode === 'replace') {
            $task->assignees()->sync([$userId => ['assigned_by_id' => $actorId, 'assigned_at' => now()]]);
            $task->forceFill(['primary_assignee_id' => $userId])->save();
        } else {
            $task->assignees()->syncWithoutDetaching([
                $userId => ['assigned_by_id' => $actorId, 'assigned_at' => now()],
            ]);
            if (! $task->primary_assignee_id) {
                $task->forceFill(['primary_assignee_id' => $userId])->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function doAddToSprint(Task $task, array $payload, ?int $actorId): void
    {
        $sprintId = (int) ($payload['sprint_id'] ?? 0);
        if (! $sprintId) return;
        // The pivot is named on Sprint, not on Task — attach via raw query to
        // avoid bidirectional relation noise.
        \Illuminate\Support\Facades\DB::table('sprint_task')->updateOrInsert(
            ['sprint_id' => $sprintId, 'task_id' => $task->id],
            ['added_by_id' => $actorId, 'added_at' => now()],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function doComment(Task $task, array $payload, ?int $actorId): void
    {
        $body = strtr((string) ($payload['body'] ?? ''), [
            '{{task_title}}' => $task->title,
            '{{task_ref}}' => (string) $task->reference,
        ]);
        if ($body === '') return;

        TaskComment::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $actorId,
            'body' => $body,
            'mentions' => null,
        ]);
    }
}
