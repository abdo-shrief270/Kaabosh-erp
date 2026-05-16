<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintBurndownSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'sprint_id', 'snapshot_date',
        'remaining_estimate_hours', 'remaining_task_count', 'completed_task_count',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'remaining_estimate_hours' => 'decimal:2',
        'remaining_task_count' => 'integer',
        'completed_task_count' => 'integer',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}
