<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\TaskBoard\Enums\TaskPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Task extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'board_id', 'board_column_id', 'task_type_id',
        'number', 'reference',
        'title', 'description', 'priority',
        'parent_task_id', 'reporter_id', 'primary_assignee_id',
        'start_date', 'due_date', 'estimate_hours', 'logged_hours', 'progress',
        'position',
        'completed_at', 'archived_at',
        'share_token', 'shared_until',
    ];

    protected $casts = [
        'priority' => TaskPriority::class,
        'number' => 'integer',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'estimate_hours' => 'decimal:2',
        'logged_hours' => 'decimal:2',
        'progress' => 'integer',
        'position' => 'float',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'shared_until' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'board_column_id', 'task_type_id', 'title', 'description',
                'priority', 'primary_assignee_id', 'parent_task_id',
                'start_date', 'due_date', 'estimate_hours', 'progress',
                'completed_at', 'archived_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Eager-load subtasks recursively up to N levels. Real-world hierarchies
     * almost never exceed 4–5 levels; we cap at 6 to keep the query bounded.
     * Use `Task::deepSubtasksRelation()` when chaining into ->with([...]).
     */
    public static function deepSubtasksRelation(int $depth = 6): string
    {
        return str_repeat('subtasks.', max(1, $depth)).'type';
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function primaryAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_assignee_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('assigned_by_id', 'assigned_at');
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_watchers')->withTimestamps();
    }

    public function starredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_stars')->withTimestamps();
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(TaskCustomFieldValue::class);
    }

    public function mutedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_mutes')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(Version::class, 'task_version');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('position');
    }

    /**
     * Flattened items across all of this task's checklists. Used with
     * `withCount` to compute the per-card progress badge ("2/5") without
     * eager-loading the whole checklist tree on Kanban list payloads.
     */
    public function checklistItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            TaskChecklistItem::class,
            TaskChecklist::class,
            'task_id',         // FK on task_checklists
            'checklist_id',    // FK on task_checklist_items
            'id',
            'id',
        );
    }

    /**
     * Dependencies where this task is the actor — i.e. tasks that it
     * blocks / duplicates / relates to. Pair with `dependents` to see the
     * inverse (tasks that block this one).
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(TaskReaction::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────
    public function scopeOnBoard(Builder $q, int $boardId): Builder
    {
        return $q->where('board_id', $boardId);
    }

    public function scopeAssignedTo(Builder $q, int $userId): Builder
    {
        return $q->where('primary_assignee_id', $userId)
            ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $userId));
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNull('completed_at')->whereNull('archived_at');
    }
}
