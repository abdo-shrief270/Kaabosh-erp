<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\TaskBoard\Enums\RecurrenceFrequency;
use App\Domain\TaskBoard\Enums\TaskPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrence extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'board_column_id', 'task_type_id', 'created_by',
        'title', 'description', 'priority', 'default_assignee_id', 'default_tag_ids',
        'frequency', 'interval', 'byday', 'cron_expression', 'timezone',
        'starts_at', 'ends_at', 'max_occurrences', 'spawned_count',
        'next_spawn_at', 'last_spawned_at', 'is_active',
    ];

    protected $casts = [
        'priority' => TaskPriority::class,
        'frequency' => RecurrenceFrequency::class,
        'default_tag_ids' => 'array',
        'byday' => 'array',
        'interval' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_occurrences' => 'integer',
        'spawned_count' => 'integer',
        'next_spawn_at' => 'datetime',
        'last_spawned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDue(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where('next_spawn_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('max_occurrences')->orWhereColumn('spawned_count', '<', 'max_occurrences'));
    }
}
