<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Enums\AutomationTrigger;
use App\Domain\TaskBoard\Models\AutomationRule;
use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Services\AutomationRuleEvaluator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks'] as $p) Permission::findOrCreate($p, 'web');

    $this->user = createUser(['tenant_id' => $this->tenant->id]);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->todo = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->doing = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Doing', 'position' => 2000,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeAutoTask(array $extra, $ctx): Task
{
    return Task::create(array_merge([
        'tenant_id' => $ctx->tenant->id,
        'board_id' => $ctx->board->id,
        'board_column_id' => $ctx->todo->id,
        'task_type_id' => $ctx->type->id,
        'title' => 'Task',
        'priority' => 'medium',
        'reporter_id' => $ctx->user->id,
        'position' => 1000,
    ], $extra));
}

it('runs an action when the trigger fires and conditions match', function () {
    $rule = AutomationRule::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'name' => 'High-priority → Doing',
        'is_active' => true,
        'trigger_type' => 'task_created',
        'trigger_config' => [],
        'conditions' => [['field' => 'priority', 'op' => 'is', 'value' => 'high']],
        'actions' => [['type' => 'move_to_column', 'payload' => ['column_id' => $this->doing->id]]],
        'created_by' => $this->user->id,
        'fire_count' => 0, 'error_count' => 0,
    ]);
    $task = makeAutoTask(['priority' => 'high'], $this);

    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskCreated, $task);

    expect($task->fresh()->board_column_id)->toBe($this->doing->id);
    expect($rule->fresh()->fire_count)->toBe(1);
    expect($rule->fresh()->last_fired_at)->not->toBeNull();
});

it('skips actions when conditions do not match', function () {
    $rule = AutomationRule::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Only high', 'is_active' => true,
        'trigger_type' => 'task_created', 'trigger_config' => [],
        'conditions' => [['field' => 'priority', 'op' => 'is', 'value' => 'high']],
        'actions' => [['type' => 'move_to_column', 'payload' => ['column_id' => $this->doing->id]]],
        'created_by' => $this->user->id,
        'fire_count' => 0, 'error_count' => 0,
    ]);
    $task = makeAutoTask(['priority' => 'low'], $this);

    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskCreated, $task);

    expect($task->fresh()->board_column_id)->toBe($this->todo->id);
    expect($rule->fresh()->fire_count)->toBe(0);
});

it('inactive rules do not fire', function () {
    $rule = AutomationRule::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Disabled', 'is_active' => false,
        'trigger_type' => 'task_created', 'trigger_config' => [], 'conditions' => [],
        'actions' => [['type' => 'set_priority', 'payload' => ['priority' => 'critical']]],
        'created_by' => $this->user->id, 'fire_count' => 0, 'error_count' => 0,
    ]);
    $task = makeAutoTask([], $this);

    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskCreated, $task);

    expect((string) $task->fresh()->priority->value)->toBe('medium');
    expect($rule->fresh()->fire_count)->toBe(0);
});

it('matches trigger_config on task_moved (to_column_id)', function () {
    $rule = AutomationRule::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Bump priority when entering Doing', 'is_active' => true,
        'trigger_type' => 'task_moved',
        'trigger_config' => ['to_column_id' => $this->doing->id],
        'conditions' => [],
        'actions' => [['type' => 'set_priority', 'payload' => ['priority' => 'critical']]],
        'created_by' => $this->user->id, 'fire_count' => 0, 'error_count' => 0,
    ]);
    $task = makeAutoTask([], $this);

    // Move into the wrong column → trigger_config mismatch, no fire.
    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskMoved, $task, [
        'from_column_id' => $this->todo->id,
        'to_column_id' => $this->todo->id,
    ]);
    expect($rule->fresh()->fire_count)->toBe(0);

    // Move into Doing → fires.
    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskMoved, $task, [
        'from_column_id' => $this->todo->id,
        'to_column_id' => $this->doing->id,
    ]);
    expect($rule->fresh()->fire_count)->toBe(1);
    expect((string) $task->fresh()->priority->value)->toBe('critical');
});

it('records an error_count when an action throws', function () {
    $rule = AutomationRule::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Broken', 'is_active' => true,
        'trigger_type' => 'task_created', 'trigger_config' => [], 'conditions' => [],
        'actions' => [['type' => 'this_action_does_not_exist', 'payload' => []]],
        'created_by' => $this->user->id, 'fire_count' => 0, 'error_count' => 0,
    ]);
    $task = makeAutoTask([], $this);

    app(AutomationRuleEvaluator::class)->evaluate(AutomationTrigger::TaskCreated, $task);

    expect($rule->fresh()->error_count)->toBe(1);
    expect($rule->fresh()->last_error)->not->toBeNull();
});
