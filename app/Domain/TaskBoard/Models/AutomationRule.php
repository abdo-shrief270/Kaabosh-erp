<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'name', 'description', 'is_active',
        'trigger_type', 'trigger_config', 'conditions', 'actions',
        'created_by', 'last_fired_at', 'fire_count', 'error_count', 'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'array',
        'conditions' => 'array',
        'actions' => 'array',
        'last_fired_at' => 'datetime',
        'fire_count' => 'integer',
        'error_count' => 'integer',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForBoard(Builder $q, ?int $boardId): Builder
    {
        return $q->where(fn ($q) => $q->where('board_id', $boardId)->orWhereNull('board_id'));
    }

    public function scopeOfTrigger(Builder $q, string $triggerType): Builder
    {
        return $q->where('trigger_type', $triggerType);
    }
}
