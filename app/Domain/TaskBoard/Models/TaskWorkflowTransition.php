<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskWorkflowTransition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'board_id', 'task_type_id',
        'from_column_id', 'to_column_id',
    ];

    public function board(): BelongsTo { return $this->belongsTo(Board::class); }
    public function type(): BelongsTo  { return $this->belongsTo(TaskType::class, 'task_type_id'); }
    public function fromColumn(): BelongsTo { return $this->belongsTo(BoardColumn::class, 'from_column_id'); }
    public function toColumn(): BelongsTo   { return $this->belongsTo(BoardColumn::class, 'to_column_id'); }
}
