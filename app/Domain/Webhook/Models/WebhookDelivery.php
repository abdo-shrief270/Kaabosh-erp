<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['endpoint_id', 'event', 'payload', 'status_code', 'response_body', 'duration_ms', 'attempt', 'status', 'error_message', 'next_retry_at'])]
class WebhookDelivery extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['event', 'status', 'status_code', 'attempt'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Webhook delivery {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'attempt' => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    public function scopePendingRetry($query)
    {
        return $query->where('status', 'retrying')
            ->where('next_retry_at', '<=', now());
    }
}
