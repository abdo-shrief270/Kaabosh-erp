<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskGithubLink;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Services\GithubPrFetcher;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks'] as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo(['view_tasks', 'edit_tasks']);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Eng', 'slug' => 'eng-'.uniqid(),
        'visibility' => 'team', 'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->column = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeGithubTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $userId): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'Implement X',
        'priority' => 'medium',
        'reporter_id' => $userId,
        'position' => 1000,
    ]);
}

it('parses canonical GitHub PR URLs', function () {
    $svc = app(GithubPrFetcher::class);
    expect($svc->parseUrl('https://github.com/laravel/framework/pull/12345'))
        ->toBe(['repo' => 'laravel/framework', 'pr_number' => 12345]);
    expect($svc->parseUrl('http://www.github.com/owner/repo.name/pull/7'))
        ->toBe(['repo' => 'owner/repo.name', 'pr_number' => 7]);
});

it('rejects malformed URLs', function () {
    $svc = app(GithubPrFetcher::class);
    expect($svc->parseUrl('not a url'))->toBeNull();
    expect($svc->parseUrl('https://gitlab.com/owner/repo/-/merge_requests/1'))->toBeNull();
    expect($svc->parseUrl('https://github.com/owner/repo/issues/123'))->toBeNull();
});

it('attaches a PR link and stores the fetched state', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    Http::fake([
        'api.github.com/*' => Http::response([
            'state' => 'open',
            'draft' => false,
            'merged_at' => null,
            'title' => 'Add feature X',
            'user' => ['login' => 'alice'],
        ], 200, ['ETag' => 'W/"abc123"']),
    ]);

    $task = makeGithubTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/github-links", [
            'url' => 'https://github.com/laravel/framework/pull/12345',
        ]);

    $res->assertCreated()
        ->assertJsonPath('data.repo', 'laravel/framework')
        ->assertJsonPath('data.pr_number', 12345)
        ->assertJsonPath('data.state', 'open')
        ->assertJsonPath('data.title', 'Add feature X')
        ->assertJsonPath('data.author', 'alice');

    expect(TaskGithubLink::where('task_id', $task->id)->count())->toBe(1);
});

it('maps merged_at to "merged" and draft+open to "draft"', function () {
    $svc = app(GithubPrFetcher::class);

    Http::fake([
        'api.github.com/repos/o/r/pulls/1' => Http::response([
            'state' => 'closed', 'merged_at' => '2026-01-01T00:00:00Z',
            'title' => 'M', 'user' => ['login' => 'm'],
        ], 200),
        'api.github.com/repos/o/r/pulls/2' => Http::response([
            'state' => 'open', 'draft' => true, 'merged_at' => null,
            'title' => 'D', 'user' => ['login' => 'd'],
        ], 200),
    ]);

    $merged = TaskGithubLink::create([
        'tenant_id' => $this->tenant->id, 'task_id' => makeGithubTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id)->id,
        'repo' => 'o/r', 'pr_number' => 1, 'url' => 'https://github.com/o/r/pull/1',
    ]);
    $draft = TaskGithubLink::create([
        'tenant_id' => $this->tenant->id, 'task_id' => makeGithubTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id)->id,
        'repo' => 'o/r', 'pr_number' => 2, 'url' => 'https://github.com/o/r/pull/2',
    ]);

    $svc->refresh($merged);
    $svc->refresh($draft);

    expect($merged->fresh()->state)->toBe('merged');
    expect($draft->fresh()->state)->toBe('draft');
});

it('refuses to attach the same PR twice to the same task', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    Http::fake(['api.github.com/*' => Http::response(['state' => 'open', 'title' => 'X', 'user' => ['login' => 'a']], 200)]);

    $task = makeGithubTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);
    $url = 'https://github.com/o/r/pull/9';

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/github-links", ['url' => $url])
        ->assertCreated();

    // Second attach returns the same row (firstOrCreate) — verify by count.
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/github-links", ['url' => $url])
        ->assertCreated();

    expect(TaskGithubLink::where('task_id', $task->id)->count())->toBe(1);
});
