<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskBoardView extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'task_board_views';

    protected $fillable = [
        'tenant_id', 'board_id', 'user_id', 'name', 'view_mode',
        'filters', 'is_shared', 'is_pinned', 'position',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_shared' => 'boolean',
        'is_pinned' => 'boolean',
        'position' => 'float',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
