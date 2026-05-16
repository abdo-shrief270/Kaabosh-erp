<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplate extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'task_type_id', 'created_by',
        'name', 'icon', 'description',
        'title_template', 'body_template', 'priority',
        'default_estimate_hours', 'default_tag_ids', 'default_checklist',
        'is_system', 'use_count',
    ];

    protected $casts = [
        'default_tag_ids' => 'array',
        'default_checklist' => 'array',
        'default_estimate_hours' => 'decimal:2',
        'is_system' => 'boolean',
        'use_count' => 'integer',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
