<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Services\TaskMovementService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'edit_tasks']);

    $this->source = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Source',
        'slug' => 'src-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'SRC',
    ]);
    $this->target = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Target',
        'slug' => 'tgt-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'TGT',
    ]);

    $this->srcCol = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->source->id, 'name' => 'Todo', 'position' => 1000, 'is_initial' => true]);
    $this->tgtInitial = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->target->id, 'name' => 'Backlog', 'position' => 1000, 'is_initial' => true]);
    $this->tgtCol = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->target->id, 'name' => 'In Progress', 'position' => 2000]);

    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeXBoardTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $userId, ?int $parentId = null): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'parent_task_id' => $parentId,
        'title' => 'Task',
        'priority' => 'medium',
        'reporter_id' => $userId,
        'position' => 1000,
    ]);
}

it('recursively moves a parent + its subtree to a new board, regenerating references', function () {
    $svc = app(TaskMovementService::class);
    $parent = makeXBoardTask($this->tenant->id, $this->source->id, $this->srcCol->id, $this->type->id, $this->user->id);
    $child1 = makeXBoardTask($this->tenant->id, $this->source->id, $this->srcCol->id, $this->type->id, $this->user->id, $parent->id);
    $child2 = makeXBoardTask($this->tenant->id, $this->source->id, $this->srcCol->id, $this->type->id, $this->user->id, $parent->id);
    $grand  = makeXBoardTask($this->tenant->id, $this->source->id, $this->srcCol->id, $this->type->id, $this->user->id, $child1->id);

    $svc->moveTaskCrossBoard($parent, $this->tgtCol);

    expect($parent->fresh()->board_id)->toBe($this->target->id)
        ->and($parent->fresh()->board_column_id)->toBe($this->tgtCol->id)
        ->and($parent->fresh()->reference)->toStartWith('TGT-');

    // Children land on the new board too, in its initial column.
    foreach ([$child1, $child2, $grand] as $c) {
        $fresh = $c->fresh();
        expect($fresh->board_id)->toBe($this->target->id)
            ->and($fresh->board_column_id)->toBe($this->tgtInitial->id)
            ->and($fresh->reference)->toStartWith('TGT-');
    }
    // Parent-child links survive.
    expect($child1->fresh()->parent_task_id)->toBe($parent->id);
    expect($grand->fresh()->parent_task_id)->toBe($child1->id);
});

it('drops board-scoped relations on cross-board move', function () {
    $svc = app(TaskMovementService::class);
    $tag = \App\Domain\TaskBoard\Models\Tag::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->source->id,
        'name' => 'urgent',
        'slug' => 'urgent',
        'color' => '#f00',
    ]);
    $parent = makeXBoardTask($this->tenant->id, $this->source->id, $this->srcCol->id, $this->type->id, $this->user->id);
    \Illuminate\Support\Facades\DB::table('task_tag')->insert(['task_id' => $parent->id, 'tag_id' => $tag->id]);

    $svc->moveTaskCrossBoard($parent, $this->tgtCol);

    expect(\Illuminate\Support\Facades\DB::table('task_tag')->where('task_id', $parent->id)->count())
        ->toBe(0);
});
