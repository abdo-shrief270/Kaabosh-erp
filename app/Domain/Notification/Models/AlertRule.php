<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('alert_rules')]
#[Fillable([
    'tenant_id',
    'name_ar',
    'name_en',
    'metric',
    'operator',
    'threshold',
    'check_frequency',
    'notification_channels',
    'recipients',
    'is_active',
    'last_triggered_at',
    'trigger_count',
    'cooldown_hours',
    'created_by',
])]
class AlertRule extends Model
{
    use BelongsToTenant;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'threshold' => 'decimal:2',
            'notification_channels' => 'array',
            'recipients' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'trigger_count' => 'integer',
            'cooldown_hours' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_ar', 'metric', 'operator', 'threshold', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Alert rule {$eventName}");
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForFrequency(Builder $query, string $frequency): Builder
    {
        return $query->where('check_frequency', $frequency);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    /**
     * Check if the rule is within its cooldown period and should not re-trigger.
     */
    public function isInCooldown(): bool
    {
        if ($this->last_triggered_at === null) {
            return false;
        }

        return $this->last_triggered_at->addHours($this->cooldown_hours)->isFuture();
    }

    /**
     * Evaluate whether the given metric value satisfies the rule's condition.
     */
    public function conditionMet(string $metricValue): bool
    {
        $threshold = (string) $this->threshold;

        return match ($this->operator) {
            'gt' => bccomp($metricValue, $threshold, 2) === 1,
            'gte' => bccomp($metricValue, $threshold, 2) >= 0,
            'lt' => bccomp($metricValue, $threshold, 2) === -1,
            'lte' => bccomp($metricValue, $threshold, 2) <= 0,
            'eq' => bccomp($metricValue, $threshold, 2) === 0,
            default => false,
        };
    }
}
