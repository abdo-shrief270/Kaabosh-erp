<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\TaskBoardWebhook;
use App\Domain\TaskBoard\Models\TaskBoardWebhookDelivery;
use App\Jobs\SendTaskBoardWebhookJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->admin = createUser(['tenant_id' => $this->tenant->id]);
    $this->admin->givePermissionTo(['view_tasks', 'manage_boards']);
    actingAsUser($this->admin);
    $this->be($this->admin);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
});

it('records each delivery attempt with status, latency, and excerpt', function () {
    Http::fake([
        'example.com/*' => Http::response('{"ok":true}', 202),
    ]);

    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'label' => 'demo', 'url' => 'https://example.com/hook',
        'format' => 'generic', 'events' => ['task.created'], 'is_active' => true,
    ]);

    $job = new SendTaskBoardWebhookJob($hook->id, 'task.created', [
        'summary' => 'Hi',
        'task' => ['id' => 1, 'reference' => 'ENG-1', 'title' => 'Hi', 'board_id' => $this->board->id, 'url' => null],
    ]);
    $job->handle();

    $row = TaskBoardWebhookDelivery::where('webhook_id', $hook->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->response_status)->toBe(202)
        ->and($row->succeeded)->toBeTrue()
        ->and($row->response_body_excerpt)->toContain('"ok":true')
        ->and($row->latency_ms)->toBeInt();
});

it('records a failed delivery with the error excerpt', function () {
    Http::fake([
        'example.com/*' => Http::response('boom', 500),
    ]);

    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'label' => 'failsy', 'url' => 'https://example.com/hook',
        'format' => 'generic', 'events' => ['task.created'], 'is_active' => true,
    ]);

    $job = new SendTaskBoardWebhookJob($hook->id, 'task.created', [
        'summary' => 'Hi',
        'task' => ['id' => 1, 'reference' => 'ENG-1', 'title' => 'Hi', 'board_id' => $this->board->id, 'url' => null],
    ]);
    // The job releases on 5xx, but recordDelivery still runs.
    try { $job->handle(); } catch (\Throwable) { /* release / re-throw paths */ }

    $row = TaskBoardWebhookDelivery::where('webhook_id', $hook->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->response_status)->toBe(500)
        ->and($row->succeeded)->toBeFalse();
});

it('GET /deliveries returns the recent history for a hook', function () {
    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'label' => 'demo', 'url' => 'https://example.com/hook',
        'format' => 'generic', 'events' => ['task.created'], 'is_active' => true,
    ]);
    TaskBoardWebhookDelivery::create([
        'webhook_id' => $hook->id, 'event_key' => 'task.created',
        'payload' => ['payload' => ['summary' => 'A']],
        'response_status' => 200, 'succeeded' => true, 'latency_ms' => 42,
        'delivered_at' => now(),
    ]);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson("/api/v1/task-board/webhooks/{$hook->id}/deliveries");
    $res->assertOk();
    expect($res->json('data'))->toHaveCount(1);
    expect($res->json('data.0.succeeded'))->toBeTrue();
    expect($res->json('data.0.response_status'))->toBe(200);
});

it('POST /replay re-queues the delivery as a fresh job', function () {
    Queue::fake();

    $hook = TaskBoardWebhook::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'label' => 'demo', 'url' => 'https://example.com/hook',
        'format' => 'generic', 'events' => ['task.created'], 'is_active' => true,
    ]);
    $delivery = TaskBoardWebhookDelivery::create([
        'webhook_id' => $hook->id, 'event_key' => 'task.created',
        'payload' => ['payload' => ['summary' => 'Replay me']],
        'response_status' => 500, 'succeeded' => false, 'latency_ms' => 9000,
        'delivered_at' => now(),
    ]);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/task-board/webhook-deliveries/{$delivery->id}/replay")
        ->assertOk();

    Queue::assertPushed(SendTaskBoardWebhookJob::class, 1);
});
