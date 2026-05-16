<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\TaskBoard\Enums\SprintStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'name', 'status', 'goal',
        'starts_at', 'ends_at', 'started_at', 'completed_at',
        'committed_estimate_hours', 'committed_task_count', 'created_by',
    ];

    protected $casts = [
        'status' => SprintStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'committed_estimate_hours' => 'decimal:2',
        'committed_task_count' => 'integer',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'sprint_task')
            ->withPivot('added_by_id', 'added_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SprintBurndownSnapshot::class)->orderBy('snapshot_date');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', SprintStatus::Active->value);
    }
}
