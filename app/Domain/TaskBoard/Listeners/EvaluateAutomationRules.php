<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Listeners;

use App\Domain\TaskBoard\Enums\AutomationTrigger;
use App\Domain\TaskBoard\Events\TaskAssigneesChanged;
use App\Domain\TaskBoard\Events\TaskCreated;
use App\Domain\TaskBoard\Events\TaskMoved;
use App\Domain\TaskBoard\Services\AutomationRuleEvaluator;

/**
 * Routes domain events to the automation evaluator. The dispatcher in
 * AppServiceProvider wires `handle*` methods to each event class.
 *
 * Listeners are deliberately sync (not queued) for v1 — automations should
 * land before the task GET returns so the UI shows the post-automation
 * state. If a rule is slow, move that specific action behind a job.
 */
class EvaluateAutomationRules
{
    public function __construct(private readonly AutomationRuleEvaluator $evaluator) {}

    public function handleTaskCreated(TaskCreated $event): void
    {
        $this->evaluator->evaluate(
            trigger: AutomationTrigger::TaskCreated,
            task: $event->task,
            eventPayload: [],
            actorId: $event->actorId,
        );
    }

    public function handleTaskMoved(TaskMoved $event): void
    {
        $this->evaluator->evaluate(
            trigger: AutomationTrigger::TaskMoved,
            task: $event->task,
            eventPayload: [
                'from_column_id' => $event->fromColumnId,
                'to_column_id' => $event->toColumnId,
            ],
            actorId: $event->actorId,
        );

        if ($event->enteredDoneColumn) {
            $this->evaluator->evaluate(
                trigger: AutomationTrigger::TaskCompleted,
                task: $event->task,
                eventPayload: [],
                actorId: $event->actorId,
            );
        }
    }

    public function handleTaskAssigneesChanged(TaskAssigneesChanged $event): void
    {
        foreach ($event->added as $userId) {
            $this->evaluator->evaluate(
                trigger: AutomationTrigger::TaskAssigned,
                task: $event->task,
                eventPayload: ['user_id' => (int) $userId],
                actorId: $event->actorId,
            );
        }
    }
}
