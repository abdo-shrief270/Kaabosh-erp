<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\BoardCustomField;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskCustomFieldValue;
use App\Domain\TaskBoard\Models\TaskType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'edit_tasks', 'manage_boards']);
    actingAsUser($this->user);
    $this->be($this->user);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->column = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeCfTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $userId): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'Task',
        'priority' => 'medium',
        'reporter_id' => $userId,
        'position' => 1000,
    ]);
}

it('creates fields with a stable slugified key', function () {
    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/boards/{$this->board->id}/custom-fields", [
            'label' => 'Story Points', 'type' => 'number',
        ]);
    $res->assertCreated();
    expect($res->json('data.key'))->toBe('story_points');
});

it('refuses to change type once a value exists', function () {
    $field = BoardCustomField::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'key' => 'severity', 'label' => 'Severity', 'type' => 'select',
        'options' => ['Low', 'High'], 'position' => 1000,
    ]);
    $task = makeCfTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);
    TaskCustomFieldValue::create([
        'task_id' => $task->id, 'custom_field_id' => $field->id, 'value' => 'Low',
    ]);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/board-custom-fields/{$field->id}", ['type' => 'text'])
        ->assertStatus(422);

    expect($field->fresh()->type)->toBe('select');
});

it('coerces and validates values per type on upsert', function () {
    $num = BoardCustomField::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'key' => 'points', 'label' => 'Points', 'type' => 'number', 'position' => 1000,
    ]);
    $sel = BoardCustomField::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'key' => 'sev', 'label' => 'Severity', 'type' => 'select',
        'options' => ['Low', 'High'], 'position' => 2000,
    ]);
    $task = makeCfTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);

    // Valid number + valid select.
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/tasks/{$task->id}/custom-fields", [
            'values' => [(string) $num->id => '8', (string) $sel->id => 'High'],
        ])
        ->assertOk();

    $stored = TaskCustomFieldValue::where('task_id', $task->id)->get()->keyBy('custom_field_id');
    // Float `8.0` round-trips through Postgres JSONB as int `8` — value-equal is the right assertion.
    expect((float) $stored[$num->id]->value)->toBe(8.0);
    expect($stored[$sel->id]->value)->toBe('High');

    // Invalid select option → 422.
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/tasks/{$task->id}/custom-fields", [
            'values' => [(string) $sel->id => 'Mythical'],
        ])
        ->assertStatus(422);
});

it('clears a value by sending an empty string', function () {
    $field = BoardCustomField::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'key' => 'note', 'label' => 'Note', 'type' => 'text', 'position' => 1000,
    ]);
    $task = makeCfTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);
    TaskCustomFieldValue::create([
        'task_id' => $task->id, 'custom_field_id' => $field->id, 'value' => 'old',
    ]);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/tasks/{$task->id}/custom-fields", [
            'values' => [(string) $field->id => ''],
        ])
        ->assertOk();

    expect(TaskCustomFieldValue::where('task_id', $task->id)->count())->toBe(0);
});
