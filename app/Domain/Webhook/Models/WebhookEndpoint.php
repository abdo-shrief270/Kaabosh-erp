<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['tenant_id', 'url', 'secret', 'events', 'description', 'is_active', 'last_triggered_at', 'failure_count'])]
class WebhookEndpoint extends Model
{
    use BelongsToTenant;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['url', 'events', 'is_active', 'description'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Webhook endpoint {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'secret' => 'encrypted',
            'last_triggered_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? []) || in_array('*', $this->events ?? []);
    }

    public function recordFailure(): void
    {
        $this->increment('failure_count');

        // Auto-disable after 50 consecutive failures
        if ($this->failure_count >= 50) {
            $this->update(['is_active' => false]);
        }
    }

    public function resetFailures(): void
    {
        $this->update(['failure_count' => 0, 'last_triggered_at' => now()]);
    }
}
