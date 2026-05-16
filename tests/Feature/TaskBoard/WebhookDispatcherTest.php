<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskBoardWebhook;
use App\Domain\TaskBoard\Models\TaskType;
use App\Domain\TaskBoard\Services\WebhookDispatcher;
use App\Jobs\SendTaskBoardWebhookJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }

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
    $this->user = createUser(['tenant_id' => $this->tenant->id]);
});

function makeWebhookTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $reporterId): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'A story',
        'priority' => 'medium',
        'reporter_id' => $reporterId,
        'position' => 1000,
    ]);
}

it('detects slack URLs and emits the slack payload shape', function () {
    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'eng-room',
        'url' => 'https://hooks.slack.com/services/T000/B000/xxx',
        'format' => 'auto',
        'events' => ['task.created'],
        'is_active' => true,
    ]);
    expect($hook->effectiveFormat())->toBe('slack');
});

it('detects discord URLs and emits the discord payload shape', function () {
    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'guild-feed',
        'url' => 'https://discord.com/api/webhooks/123/yyy',
        'format' => 'auto',
        'events' => ['task.created'],
        'is_active' => true,
    ]);
    expect($hook->effectiveFormat())->toBe('discord');
});

it('falls back to generic for unknown hosts', function () {
    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'our-zap',
        'url' => 'https://hooks.zapier.com/blah',
        'format' => 'auto',
        'events' => ['task.created'],
        'is_active' => true,
    ]);
    expect($hook->effectiveFormat())->toBe('generic');
});

it('dispatcher queues one job per matching active subscription', function () {
    Queue::fake();

    TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'on-create',
        'url' => 'https://example.com/hook',
        'format' => 'generic',
        'events' => ['task.created'],
        'is_active' => true,
    ]);
    // Different event — must NOT fire.
    TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'on-move-only',
        'url' => 'https://example.com/other',
        'format' => 'generic',
        'events' => ['task.moved'],
        'is_active' => true,
    ]);
    // Disabled — must NOT fire.
    TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'paused',
        'url' => 'https://example.com/paused',
        'format' => 'generic',
        'events' => ['task.created'],
        'is_active' => false,
    ]);
    // Tenant-wide (board_id null) — also fires.
    TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => null,
        'label' => 'workspace',
        'url' => 'https://example.com/workspace',
        'format' => 'generic',
        'events' => ['task.created'],
        'is_active' => true,
    ]);

    $task = makeWebhookTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id);

    app(WebhookDispatcher::class)->dispatchTaskEvent('task.created', $task, $this->user);

    // Expected: 2 jobs (on-create + workspace), skipping paused and on-move-only.
    Queue::assertPushed(SendTaskBoardWebhookJob::class, 2);
});

it('signs generic payloads with HMAC-SHA256 and exposes delivery headers', function () {
    Http::fake([
        '*' => Http::response('ok', 200),
    ]);

    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'label' => 'generic',
        'url' => 'https://example.com/hook',
        'format' => 'generic',
        'events' => ['task.created'],
        'secret' => 'topsecret-test',
        'is_active' => true,
    ]);

    $job = new SendTaskBoardWebhookJob($hook->id, 'task.created', [
        'summary' => 'Hello',
        'task' => ['id' => 1, 'reference' => 'ENG-1', 'title' => 'Hi', 'board_id' => $this->board->id, 'url' => null],
        'actor' => null,
    ]);
    $job->handle();

    Http::assertSent(function ($request) {
        $sig = $request->header('X-Kaabosh-Signature')[0] ?? '';
        $body = $request->body();
        $expected = 'sha256='.hash_hmac('sha256', $body, 'topsecret-test');
        return $sig === $expected
            && ($request->header('X-Kaabosh-Event')[0] ?? '') === 'task.created'
            && str_contains($body, '"event":"task.created"');
    });

    expect($hook->fresh()->last_succeeded_at)->not->toBeNull();
});
