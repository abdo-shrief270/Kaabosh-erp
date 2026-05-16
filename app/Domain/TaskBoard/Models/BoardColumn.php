<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardColumn extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'name', 'color', 'position',
        'wip_limit', 'enforce_wip', 'requires_approval', 'approver_user_ids',
        'is_done', 'is_initial',
    ];

    protected $casts = [
        'position' => 'float',
        'wip_limit' => 'integer',
        'enforce_wip' => 'boolean',
        'requires_approval' => 'boolean',
        'approver_user_ids' => 'array',
        'is_done' => 'boolean',
        'is_initial' => 'boolean',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}
