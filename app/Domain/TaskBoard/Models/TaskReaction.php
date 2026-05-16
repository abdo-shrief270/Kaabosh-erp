<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReaction extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'task_id', 'user_id', 'emoji', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
