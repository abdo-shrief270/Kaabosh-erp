<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\TaskBoard\Enums\VersionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Version extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'board_id', 'name', 'slug', 'description',
        'status', 'release_date', 'released_at', 'color',
    ];

    protected $casts = [
        'status' => VersionStatus::class,
        'release_date' => 'date',
        'released_at' => 'datetime',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_version');
    }
}
