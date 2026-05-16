<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\TaskBoard\Enums\BoardVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'description', 'color', 'icon',
        'visibility', 'is_default', 'is_archived', 'key', 'next_task_number',
        'auto_archive_completed_after_days',
        'created_by',
    ];

    protected $casts = [
        'visibility' => BoardVisibility::class,
        'is_default' => 'boolean',
        'is_archived' => 'boolean',
        'next_task_number' => 'integer',
        'auto_archive_completed_after_days' => 'integer',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }

    public function recurrences(): HasMany
    {
        return $this->hasMany(TaskRecurrence::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(BoardCustomField::class)->orderBy('position');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_archived', false);
    }
}
