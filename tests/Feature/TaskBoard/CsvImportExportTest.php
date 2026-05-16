<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Tag;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards']);
    actingAsUser($this->user);
    $this->be($this->user);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->todo = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->done = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Done', 'position' => 2000, 'is_done' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeCsvTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $userId, array $extra = []): Task
{
    return Task::create(array_merge([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'Task',
        'priority' => 'high',
        'reporter_id' => $userId,
        'position' => 1000,
    ], $extra));
}

it('exports board tasks as a UTF-8 BOM CSV with the expected header', function () {
    makeCsvTask($this->tenant->id, $this->board->id, $this->todo->id, $this->type->id, $this->user->id, [
        'title' => 'Build login',
    ]);
    makeCsvTask($this->tenant->id, $this->board->id, $this->done->id, $this->type->id, $this->user->id, [
        'title' => 'Set up CI',
    ]);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->get("/api/v1/boards/{$this->board->id}/csv");

    $res->assertOk();
    $body = $res->streamedContent();
    // BOM + header
    expect($body)->toStartWith("\xEF\xBB\xBF")
        ->and($body)->toContain('reference,title,description,status,priority')
        ->and($body)->toContain('Build login')
        ->and($body)->toContain('Set up CI');
});

it('imports rows mapping status name → column id', function () {
    $csv = "title,status,priority\nFix the bug,Done,critical\nWrite docs,Todo,low\n";
    $file = UploadedFile::fake()->createWithContent('rows.csv', $csv);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->post("/api/v1/boards/{$this->board->id}/csv", ['file' => $file]);

    $res->assertOk();
    expect($res->json('created'))->toBe(2);

    $byTitle = Task::where('board_id', $this->board->id)->get()->keyBy('title');
    expect($byTitle['Fix the bug']->board_column_id)->toBe($this->done->id);
    expect((string) $byTitle['Fix the bug']->priority->value)->toBe('critical');
    expect($byTitle['Write docs']->board_column_id)->toBe($this->todo->id);
});

it('auto-creates referenced tags', function () {
    $csv = "title,tags\nUrgent thing,backend|urgent\n";
    $file = UploadedFile::fake()->createWithContent('rows.csv', $csv);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->post("/api/v1/boards/{$this->board->id}/csv", ['file' => $file])
        ->assertOk();

    expect(Tag::where('board_id', $this->board->id)->pluck('name')->all())
        ->toContain('backend', 'urgent');

    $task = Task::where('board_id', $this->board->id)->first();
    expect($task->tags()->count())->toBe(2);
});

it('resolves parent_reference forward references in a second pass', function () {
    // Row order is child-first; parent appears below.
    $csv = "title,parent_reference\nChild,REF-A\nParent task,REF-A\n";
    $file = UploadedFile::fake()->createWithContent('rows.csv', $csv);

    // Pre-create a Parent task with reference REF-A on the same board so
    // the lookup hits the DB path (not the in-batch one).
    $parent = makeCsvTask($this->tenant->id, $this->board->id, $this->todo->id, $this->type->id, $this->user->id, [
        'title' => 'Parent existing', 'reference' => 'REF-A', 'number' => 999,
    ]);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->post("/api/v1/boards/{$this->board->id}/csv", ['file' => $file])
        ->assertOk();

    $child = Task::where('title', 'Child')->first();
    expect($child->parent_task_id)->toBe($parent->id);
});
