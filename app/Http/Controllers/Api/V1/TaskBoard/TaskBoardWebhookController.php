<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\TaskBoardWebhook;
use App\Http\Controllers\Controller;
use App\Jobs\SendTaskBoardWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tenant-scoped webhook subscriptions. Per-board or workspace-wide.
 * Writers need `manage_boards`; ping/test is open to anyone who can
 * view tasks (rate-limited via route middleware).
 */
class TaskBoardWebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $hooks = TaskBoardWebhook::query()
            ->where('tenant_id', app('tenant.id'))
            ->when($request->integer('board_id'), fn ($q, $v) => $q->where('board_id', $v))
            ->latest('id')
            ->get();

        // Hide the secret in list responses — only echoed on create.
        return response()->json(['data' => $hooks->map(fn ($h) => $this->present($h, withSecret: false))->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $this->validatePayload($request);

        $hook = TaskBoardWebhook::create([
            'tenant_id' => app('tenant.id'),
            'board_id' => $data['board_id'] ?? null,
            'label' => $data['label'],
            'url' => $data['url'],
            'format' => $data['format'] ?? 'auto',
            'events' => $data['events'],
            'secret' => Str::random(40),
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Return secret ONCE on creation; subsequent reads omit it.
        return response()->json(['data' => $this->present($hook, withSecret: true)], 201);
    }

    public function update(Request $request, TaskBoardWebhook $webhook): JsonResponse
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        abort_unless($webhook->tenant_id === (int) app('tenant.id'), 403);

        $data = $this->validatePayload($request, partial: true);

        $webhook->fill(array_intersect_key($data, array_flip([
            'board_id', 'label', 'url', 'format', 'events', 'is_active',
        ])))->save();

        return response()->json(['data' => $this->present($webhook->fresh(), withSecret: false)]);
    }

    public function destroy(Request $request, TaskBoardWebhook $webhook)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        abort_unless($webhook->tenant_id === (int) app('tenant.id'), 403);

        $webhook->delete();

        return response()->noContent();
    }

    /**
     * Fire a synthetic 'webhook.test' event so the user can confirm their
     * receiver picks it up. Queued like a real delivery so retry + error
     * surfacing matches production behaviour.
     */
    public function deliveries(Request $request, TaskBoardWebhook $webhook): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);
        abort_unless($webhook->tenant_id === (int) app('tenant.id'), 403);

        $rows = \App\Domain\TaskBoard\Models\TaskBoardWebhookDelivery::query()
            ->where('webhook_id', $webhook->id)
            ->latest('delivered_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'event_key' => $r->event_key,
            'response_status' => $r->response_status,
            'response_body_excerpt' => $r->response_body_excerpt,
            'latency_ms' => $r->latency_ms,
            'succeeded' => (bool) $r->succeeded,
            'error' => $r->error,
            'delivered_at' => $r->delivered_at,
            'payload' => $r->payload,
        ])]);
    }

    public function replay(Request $request, \App\Domain\TaskBoard\Models\TaskBoardWebhookDelivery $delivery): JsonResponse
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $hook = $delivery->webhook;
        abort_unless($hook && $hook->tenant_id === (int) app('tenant.id'), 403);

        $envelope = (array) ($delivery->payload['payload'] ?? $delivery->payload ?? []);
        SendTaskBoardWebhookJob::dispatch((int) $hook->id, $delivery->event_key, $envelope ?: ['summary' => 'Replay']);

        return response()->json(['queued' => true]);
    }

    public function test(Request $request, TaskBoardWebhook $webhook): JsonResponse
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        abort_unless($webhook->tenant_id === (int) app('tenant.id'), 403);

        SendTaskBoardWebhookJob::dispatch((int) $webhook->id, 'webhook.test', [
            'summary' => 'Test ping from Kaabosh',
            'task' => [
                'id' => 0, 'reference' => 'TEST-0', 'title' => 'Test event',
                'board_id' => $webhook->board_id, 'url' => null,
            ],
            'actor' => $request->user()?->only(['id', 'name']) ?: null,
        ]);

        return response()->json(['queued' => true]);
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'board_id' => ['nullable', 'integer', 'exists:boards,id,tenant_id,'.app('tenant.id')],
            'label' => [$required, 'string', 'max:120'],
            'url' => [$required, 'url', 'max:500'],
            'format' => ['nullable', 'in:'.implode(',', TaskBoardWebhook::FORMATS)],
            'events' => [$required, 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', TaskBoardWebhook::EVENTS)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function present(TaskBoardWebhook $h, bool $withSecret = false): array
    {
        return [
            'id' => $h->id,
            'board_id' => $h->board_id,
            'label' => $h->label,
            'url' => $h->url,
            'format' => $h->format,
            'effective_format' => $h->effectiveFormat(),
            'events' => $h->events ?? [],
            'is_active' => (bool) $h->is_active,
            'last_succeeded_at' => $h->last_succeeded_at,
            'last_failed_at' => $h->last_failed_at,
            'last_error' => $h->last_error,
            'secret' => $withSecret ? $h->secret : null,
            'created_at' => $h->created_at,
        ];
    }
}
