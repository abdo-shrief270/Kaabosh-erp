<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('usage_records')]
#[Fillable([
    'tenant_id',
    'recorded_at',
    'users_count',
    'clients_count',
    'invoices_count',
    'bills_count',
    'journal_entries_count',
    'bank_imports_count',
    'documents_count',
    'api_calls_count',
    'storage_bytes',
    'metadata',
])]
class UsageRecord extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
            'users_count' => 'integer',
            'clients_count' => 'integer',
            'invoices_count' => 'integer',
            'bills_count' => 'integer',
            'journal_entries_count' => 'integer',
            'bank_imports_count' => 'integer',
            'documents_count' => 'integer',
            'api_calls_count' => 'integer',
            'storage_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'users_count' => 0,
        'clients_count' => 0,
        'invoices_count' => 0,
        'bills_count' => 0,
        'journal_entries_count' => 0,
        'bank_imports_count' => 0,
        'documents_count' => 0,
        'api_calls_count' => 0,
        'storage_bytes' => 0,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('recorded_at', $date);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function storageForHumans(): string
    {
        $bytes = $this->storage_bytes;

        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
