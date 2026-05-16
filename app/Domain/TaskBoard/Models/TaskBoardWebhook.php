<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskBoardWebhook extends Model
{
    use BelongsToTenant;

    public const FORMATS = ['auto', 'slack', 'discord', 'generic'];

    public const EVENTS = [
        'task.created', 'task.moved', 'task.updated', 'task.deleted',
        'task.completed', 'comment.added',
        'approval.requested', 'approval.decided',
    ];

    protected $fillable = [
        'tenant_id', 'board_id', 'label', 'url', 'format',
        'events', 'secret', 'is_active',
        'last_succeeded_at', 'last_failed_at', 'last_error',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_succeeded_at' => 'datetime',
        'last_failed_at' => 'datetime',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** Resolve effective format — translates 'auto' to a concrete shape based on the URL host. */
    public function effectiveFormat(): string
    {
        if ($this->format !== 'auto') return $this->format;
        $host = parse_url((string) $this->url, PHP_URL_HOST) ?? '';
        if (str_contains($host, 'hooks.slack.com')) return 'slack';
        if (str_contains($host, 'discord.com') || str_contains($host, 'discordapp.com')) return 'discord';
        return 'generic';
    }
}
