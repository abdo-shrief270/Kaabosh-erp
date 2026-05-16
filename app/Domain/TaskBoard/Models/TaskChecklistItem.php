<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'task_checklist_items';

    protected $fillable = [
        'checklist_id', 'text', 'is_done', 'position',
        'completed_by_id', 'completed_at',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'position' => 'float',
        'completed_at' => 'datetime',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TaskChecklist::class, 'checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }
}
