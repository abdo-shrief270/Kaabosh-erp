<?php

declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('storage_quotas')]
#[Fillable([
    'tenant_id',
    'max_bytes',
    'used_bytes',
    'max_files',
    'used_files',
])]
class StorageQuota extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'max_bytes' => 'integer',
            'used_bytes' => 'integer',
            'max_files' => 'integer',
            'used_files' => 'integer',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'max_bytes' => 1073741824,
        'used_bytes' => 0,
        'max_files' => 5000,
        'used_files' => 0,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function usagePercent(): float
    {
        if ($this->max_bytes === 0) {
            return 0.0;
        }

        return ($this->used_bytes / $this->max_bytes) * 100;
    }

    public function remainingBytes(): int
    {
        return $this->max_bytes - $this->used_bytes;
    }

    public function remainingFiles(): int
    {
        return $this->max_files - $this->used_files;
    }

    public function hasSpaceFor(int $bytes): bool
    {
        return ($this->used_bytes + $bytes) <= $this->max_bytes
            && $this->used_files < $this->max_files;
    }

    public function maxBytesForHumans(): string
    {
        return $this->formatBytes($this->max_bytes);
    }

    public function usedBytesForHumans(): string
    {
        return $this->formatBytes($this->used_bytes);
    }

    private function formatBytes(int $bytes): string
    {
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
