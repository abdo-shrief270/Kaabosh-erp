<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['request_id', 'method', 'path', 'status_code', 'duration_ms', 'ip', 'user_agent', 'user_id', 'tenant_id', 'request_size', 'response_size', 'request_headers', 'request_body', 'error_message', 'created_at'])]
class ApiRequestLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'duration_ms' => 'integer',
            'status_code' => 'integer',
            'request_size' => 'integer',
            'response_size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function scopeSlow($query, int $threshold = 1000)
    {
        return $query->where('duration_ms', '>=', $threshold);
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
