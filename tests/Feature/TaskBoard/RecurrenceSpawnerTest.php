<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskRecurrence;
use App\Domain\TaskBoard\Models\TaskType;
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
    $this->column = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'S', 'slug' => 's']);
});

function makeRecurrence(array $extra, $ctx): TaskRecurrence
{
    return TaskRecurrence::create(array_merge([
        'tenant_id' => $ctx->tenant->id,
        'board_id' => $ctx->board->id,
        'board_column_id' => $ctx->column->id,
        'task_type_id' => $ctx->type->id,
        'created_by' => $ctx->user->id,
        'title' => 'Weekly standup notes',
        'priority' => 'medium',
        'frequency' => 'daily',
        'interval' => 1,
        'timezone' => 'UTC',
        'starts_at' => now()->subDay(),
        'is_active' => true,
        'spawned_count' => 0,
        'next_spawn_at' => now()->subMinute(),
    ], $extra));
}

it('spawns due recurrences and advances next_spawn_at', function () {
    $r = makeRecurrence([], $this);

    $this->artisan('task-board:spawn-recurrences')->assertSuccessful();

    expect(Task::where('board_id', $this->board->id)->count())->toBe(1);
    $fresh = $r->fresh();
    expect($fresh->spawned_count)->toBe(1);
    expect($fresh->last_spawned_at)->not->toBeNull();
    expect($fresh->next_spawn_at)->not->toBeNull();
    expect($fresh->next_spawn_at->isFuture())->toBeTrue();
});

it('does not spawn future recurrences', function () {
    makeRecurrence(['next_spawn_at' => now()->addHour()], $this);
    $this->artisan('task-board:spawn-recurrences')->assertSuccessful();
    expect(Task::where('board_id', $this->board->id)->count())->toBe(0);
});

it('does not spawn inactive recurrences even when due', function () {
    makeRecurrence(['is_active' => false], $this);
    $this->artisan('task-board:spawn-recurrences')->assertSuccessful();
    expect(Task::where('board_id', $this->board->id)->count())->toBe(0);
});

it('respects max_occurrences and deactivates when reached', function () {
    $r = makeRecurrence([
        'max_occurrences' => 1,
        'spawned_count' => 0,
    ], $this);

    $this->artisan('task-board:spawn-recurrences')->assertSuccessful();

    expect($r->fresh()->spawned_count)->toBe(1);
    expect($r->fresh()->is_active)->toBeFalse();

    // Second run should be a no-op now.
    $this->artisan('task-board:spawn-recurrences')->assertSuccessful();
    expect(Task::where('board_id', $this->board->id)->count())->toBe(1);
});
