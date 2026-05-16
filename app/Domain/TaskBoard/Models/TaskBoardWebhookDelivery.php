<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskBoardWebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id', 'event_key', 'payload',
        'response_status', 'response_body_excerpt', 'latency_ms',
        'succeeded', 'error', 'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'succeeded' => 'boolean',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(TaskBoardWebhook::class, 'webhook_id');
    }
}
