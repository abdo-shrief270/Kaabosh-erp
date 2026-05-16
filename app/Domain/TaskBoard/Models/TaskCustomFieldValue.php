<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCustomFieldValue extends Model
{
    protected $table = 'task_custom_field_values';

    protected $fillable = ['task_id', 'custom_field_id', 'value'];

    // Value is JSONB — same column shape works for strings, numbers,
    // booleans, ISO dates, and arrays of strings.
    protected $casts = [
        'value' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(BoardCustomField::class, 'custom_field_id');
    }
}
