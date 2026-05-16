<?php

declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Client\Models\Client;
use App\Domain\Document\Enums\DocumentCategory;
use App\Domain\Document\Enums\StorageTier;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('documents')]
#[Fillable([
    'tenant_id',
    'client_id',
    'name',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'hash',
    'category',
    'storage_tier',
    'description',
    'metadata',
    'uploaded_by',
    'is_archived',
    'archived_at',
])]
class Document extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'storage_tier' => StorageTier::class,
            'size_bytes' => 'integer',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'disk' => 'local',
        'category' => 'other',
        'storage_tier' => 'hot',
        'is_archived' => false,
    ];

    // ──────────────────────────────────────
    // Factory
    // ──────────────────────────────────────

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function sizeForHumans(): string
    {
        $bytes = $this->size_bytes;

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

    public function extension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function storagePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOfCategory(Builder $query, DocumentCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('description', 'ilike', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category', 'is_archived'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
