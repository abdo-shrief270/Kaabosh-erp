<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Sprint;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Services\SprintService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks', 'manage_boards'] as $p) Permission::findOrCreate($p, 'web');

    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'edit_tasks', 'manage_boards']);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->todo = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'S', 'slug' => 's']);
});

function makeSprintBoardTask(array $extra, $ctx): Task
{
    return Task::create(array_merge([
        'tenant_id' => $ctx->tenant->id,
        'board_id' => $ctx->board->id,
        'board_column_id' => $ctx->todo->id,
        'task_type_id' => $ctx->type->id,
        'title' => 'A task',
        'priority' => 'medium',
        'reporter_id' => $ctx->user->id,
        'position' => 1000,
    ], $extra));
}

function makeSprint(array $extra, $ctx): Sprint
{
    return Sprint::create(array_merge([
        'tenant_id' => $ctx->tenant->id,
        'board_id' => $ctx->board->id,
        'name' => 'S1',
        'status' => 'planned',
        'starts_at' => now(),
        'ends_at' => now()->addWeeks(2),
        'created_by' => $ctx->user->id,
        'committed_task_count' => 0,
        'committed_estimate_hours' => 0,
    ], $extra));
}

it('starts a planned sprint and freezes commitment metrics', function () {
    $sprint = makeSprint([], $this);
    makeSprintBoardTask(['estimate_hours' => 3], $this);
    $t2 = makeSprintBoardTask(['estimate_hours' => 5], $this);
    $t3 = makeSprintBoardTask([], $this);
    $sprint->tasks()->syncWithoutDetaching([
        $t2->id => ['added_by_id' => $this->user->id, 'added_at' => now()],
        $t3->id => ['added_by_id' => $this->user->id, 'added_at' => now()],
    ]);

    app(SprintService::class)->start($sprint);

    $fresh = $sprint->fresh();
    expect($fresh->status->value)->toBe('active');
    expect($fresh->committed_task_count)->toBe(2);
    expect((float) $fresh->committed_estimate_hours)->toBe(5.0);
});

it('refuses to start a second sprint on the same board', function () {
    $a = makeSprint([], $this);
    $b = makeSprint(['name' => 'S2'], $this);
    app(SprintService::class)->start($a);

    expect(fn () => app(SprintService::class)->start($b))
        ->toThrow(RuntimeException::class);
});

it('completes an active sprint and rolls incomplete tasks over when asked', function () {
    $a = makeSprint([], $this);
    $b = makeSprint(['name' => 'S2'], $this);

    $incomplete = makeSprintBoardTask([], $this);
    $done = makeSprintBoardTask(['completed_at' => now()], $this);
    $a->tasks()->syncWithoutDetaching([
        $incomplete->id => ['added_by_id' => $this->user->id, 'added_at' => now()],
        $done->id => ['added_by_id' => $this->user->id, 'added_at' => now()],
    ]);

    app(SprintService::class)->start($a);
    app(SprintService::class)->complete($a->fresh(), rolloverSprintId: $b->id);

    expect($a->fresh()->status->value)->toBe('completed');
    expect($b->fresh()->tasks()->pluck('tasks.id')->all())->toContain($incomplete->id);
    expect($b->fresh()->tasks()->pluck('tasks.id')->all())->not->toContain($done->id);
});

it('start writes an initial burndown snapshot', function () {
    $sprint = makeSprint([], $this);
    $t = makeSprintBoardTask(['estimate_hours' => 4], $this);
    $sprint->tasks()->syncWithoutDetaching([$t->id => ['added_by_id' => $this->user->id, 'added_at' => now()]]);

    app(SprintService::class)->start($sprint);

    expect($sprint->fresh()->snapshots()->count())->toBeGreaterThan(0);
});
