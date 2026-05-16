<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Models\TaskWorkflowTransition;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['view_tasks', 'create_tasks', 'edit_tasks', 'delete_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards']);
    actingAsUser($this->user);
    // spatie/laravel-activitylog's CauserResolver uses the default auth
    // guard (web) — attach there so it doesn't 500 on null provider when
    // the move endpoint writes a log entry.
    $this->be($this->user);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Eng',
        'slug' => 'eng',
        'visibility' => 'team',
        'next_task_number' => 1,
        'key' => 'ENG',
    ]);
    $this->todo = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->board->id, 'name' => 'Todo', 'position' => 1000, 'is_initial' => true]);
    $this->doing = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->board->id, 'name' => 'Doing', 'position' => 2000]);
    $this->qa = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->board->id, 'name' => 'QA', 'position' => 3000]);
    $this->done = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->board->id, 'name' => 'Done', 'position' => 4000, 'is_done' => true]);

    $this->bug = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Bug', 'slug' => 'bug']);
});

function makeTaskAt(BoardColumn $column, TaskType $type, int $tenantId, int $reporterId, int $position = 1000): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $column->board_id,
        'board_column_id' => $column->id,
        'task_type_id' => $type->id,
        'title' => 'A bug',
        'priority' => 'medium',
        'reporter_id' => $reporterId,
        'position' => $position,
    ]);
}

it('allows any column transition when no workflow rules exist', function () {
    $task = makeTaskAt($this->todo, $this->bug, $this->tenant->id, $this->user->id);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/move", ['board_column_id' => $this->done->id]);

    $res->assertOk();
    expect($task->fresh()->board_column_id)->toBe($this->done->id);
});

it('rejects moves outside the allow-list when rules exist for that type', function () {
    // Define: Bug can only go Todo → Doing → QA → Done.
    foreach ([[$this->todo, $this->doing], [$this->doing, $this->qa], [$this->qa, $this->done]] as [$from, $to]) {
        TaskWorkflowTransition::create([
            'tenant_id' => $this->tenant->id,
            'board_id' => $this->board->id,
            'task_type_id' => $this->bug->id,
            'from_column_id' => $from->id,
            'to_column_id' => $to->id,
        ]);
    }

    $task = makeTaskAt($this->todo, $this->bug, $this->tenant->id, $this->user->id);

    // Skipping Doing → going straight to Done is blocked.
    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/move", ['board_column_id' => $this->done->id]);

    $res->assertStatus(422);
    expect($res->json('error'))->toBe('transition_not_allowed');
    expect($task->fresh()->board_column_id)->toBe($this->todo->id);
});

it('allows whitelisted edges when rules exist', function () {
    TaskWorkflowTransition::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'task_type_id' => $this->bug->id,
        'from_column_id' => $this->todo->id,
        'to_column_id' => $this->doing->id,
    ]);

    $task = makeTaskAt($this->todo, $this->bug, $this->tenant->id, $this->user->id);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/move", ['board_column_id' => $this->doing->id]);

    $res->assertOk();
    expect($task->fresh()->board_column_id)->toBe($this->doing->id);
});

it('does not enforce rules on tasks of types with zero rules', function () {
    // Bug has rules; a different type (Feature) doesn't — so Feature moves anywhere.
    $feature = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Feature', 'slug' => 'feature']);
    TaskWorkflowTransition::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'task_type_id' => $this->bug->id,
        'from_column_id' => $this->todo->id,
        'to_column_id' => $this->doing->id,
    ]);

    $task = makeTaskAt($this->todo, $feature, $this->tenant->id, $this->user->id);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/move", ['board_column_id' => $this->done->id]);

    $res->assertOk();
});
