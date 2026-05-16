<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->user = createUser(['tenant_id' => $this->tenant->id]);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
        'auto_archive_completed_after_days' => 7,
    ]);
    $this->done = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Done', 'position' => 1000, 'is_done' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'S', 'slug' => 's']);
});

function makeAATask(array $attrs, int $tenantId, int $boardId, int $columnId, int $typeId, int $userId): Task
{
    return Task::create(array_merge([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'Task',
        'priority' => 'medium',
        'reporter_id' => $userId,
        'position' => 1000,
    ], $attrs));
}

it('archives completed tasks older than the threshold', function () {
    $old = makeAATask([
        'completed_at' => now()->subDays(10),
    ], $this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id);
    $fresh = makeAATask([
        'completed_at' => now()->subDays(3),
    ], $this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id);
    $open = makeAATask([], $this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id);

    $this->artisan('task-board:auto-archive')->assertSuccessful();

    expect($old->fresh()->archived_at)->not->toBeNull();
    expect($fresh->fresh()->archived_at)->toBeNull();
    expect($open->fresh()->archived_at)->toBeNull();
});

it('skips boards with no auto-archive policy', function () {
    $this->board->forceFill(['auto_archive_completed_after_days' => null])->save();

    $t = makeAATask(['completed_at' => now()->subDays(60)],
        $this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id);

    $this->artisan('task-board:auto-archive')->assertSuccessful();

    expect($t->fresh()->archived_at)->toBeNull();
});

it('--dry-run reports counts without writing', function () {
    $t = makeAATask(['completed_at' => now()->subDays(10)],
        $this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id);

    $this->artisan('task-board:auto-archive --dry-run')->assertSuccessful();

    expect($t->fresh()->archived_at)->toBeNull();
});
