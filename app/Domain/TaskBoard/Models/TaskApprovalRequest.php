<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskApprovalRequest extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'task_id',
        'from_column_id', 'target_column_id',
        'requested_by_user_id', 'status',
        'decided_by_user_id', 'decided_at', 'reason',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function task(): BelongsTo            { return $this->belongsTo(Task::class); }
    public function fromColumn(): BelongsTo      { return $this->belongsTo(BoardColumn::class, 'from_column_id'); }
    public function targetColumn(): BelongsTo    { return $this->belongsTo(BoardColumn::class, 'target_column_id'); }
    public function requestedBy(): BelongsTo     { return $this->belongsTo(User::class, 'requested_by_user_id'); }
    public function decidedBy(): BelongsTo       { return $this->belongsTo(User::class, 'decided_by_user_id'); }
}
