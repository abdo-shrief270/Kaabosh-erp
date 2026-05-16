<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use Illuminate\Support\Facades\Log;

/**
 * Provider-agnostic email-to-task ingestor.
 *
 *   recipient: tasks+<inbox_key>@kaabosh.tech
 *   subject:   task title
 *   text/html: task description
 *
 * The inbox_key is matched to a board with inbox_enabled = true. Tasks are
 * created in the board's initial column (is_initial=true) or the first
 * column by position as fallback. Default task type is whichever has
 * `slug = 'task'`; failing that, the first system type.
 *
 * Sender is recorded in the description as a leading line because we have
 * no concept of "external reporter" — task.reporter_id is nullable so we
 * leave it that way.
 */
class InboundEmailIngestor
{
    /**
     * @param  array{recipient: string, subject?: string, text?: string, html?: string, from?: string}  $payload
     */
    public function ingest(array $payload): ?Task
    {
        $recipient = (string) ($payload['recipient'] ?? '');
        if ($recipient === '') {
            Log::warning('Inbound email: missing recipient', $payload);
            return null;
        }

        if (! preg_match('/[+ ]([A-Za-z0-9]{6,32})@/', $recipient, $m)) {
            Log::info('Inbound email: no +inbox_key tag in recipient', ['to' => $recipient]);
            return null;
        }
        $inboxKey = $m[1];

        /** @var Board|null $board */
        $board = Board::query()
            ->withoutGlobalScopes()
            ->where('inbox_key', $inboxKey)
            ->where('inbox_enabled', true)
            ->first();
        if (! $board) {
            Log::info('Inbound email: no board for inbox_key', ['key' => $inboxKey]);
            return null;
        }

        $column = $board->columns()
            ->orderByDesc('is_initial')->orderBy('position')->first();
        if (! $column) {
            Log::warning('Inbound email: board has no columns', ['board_id' => $board->id]);
            return null;
        }

        $type = TaskType::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $board->tenant_id)
            ->orderByRaw("slug = 'task' DESC")
            ->orderBy('id')
            ->first();
        if (! $type) {
            Log::warning('Inbound email: no task type for tenant', ['tenant_id' => $board->tenant_id]);
            return null;
        }

        $subject = trim((string) ($payload['subject'] ?? '')) ?: '(no subject)';
        $body = trim((string) ($payload['text'] ?? $payload['html'] ?? ''));
        $from = (string) ($payload['from'] ?? '');
        if ($from !== '') {
            $body = "From: {$from}\n\n{$body}";
        }

        // Bind tenant so BelongsToTenant fills it on create.
        app()->instance('tenant.id', $board->tenant_id);

        return Task::query()->withoutGlobalScopes()->create([
            'tenant_id' => $board->tenant_id,
            'board_id' => $board->id,
            'board_column_id' => $column->id,
            'task_type_id' => $type->id,
            'title' => substr($subject, 0, 500),
            'description' => $body,
            'priority' => 'medium',
        ]);
    }
}
