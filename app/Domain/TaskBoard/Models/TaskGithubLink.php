<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskGithubLink extends Model
{
    use BelongsToTenant;

    public const STATE_OPEN    = 'open';
    public const STATE_CLOSED  = 'closed';
    public const STATE_MERGED  = 'merged';
    public const STATE_DRAFT   = 'draft';
    public const STATE_UNKNOWN = 'unknown';

    protected $fillable = [
        'tenant_id', 'task_id', 'repo', 'pr_number', 'url',
        'state', 'title', 'author', 'etag',
        'last_fetched_at', 'created_by_user_id',
    ];

    protected $casts = [
        'pr_number' => 'integer',
        'last_fetched_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
