<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Models;

use App\Domain\Client\Models\Client;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('timers')]
#[Fillable([
    'tenant_id',
    'user_id',
    'client_id',
    'task_description',
    'started_at',
    'stopped_at',
    'is_running',
])]
class Timer extends Model
{
    use BelongsToTenant;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_running' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
            'is_running' => 'boolean',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_running', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function elapsedHours(): float
    {
        $end = $this->stopped_at ?? now();

        return round($this->started_at->diffInMinutes($end) / 60, 2);
    }
}
