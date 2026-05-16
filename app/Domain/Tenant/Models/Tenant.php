<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use App\Domain\Firm\Models\Firm;
use App\Domain\Shared\Enums\ClientTier;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\TenantType;
use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('tenants')]
#[Fillable([
    'firm_id',
    'type',
    'client_tier',
    'name',
    'slug',
    'domain',
    'email',
    'phone',
    'tax_id',
    'commercial_register',
    'address',
    'city',
    'status',
    'settings',
    'trial_ends_at',
    'logo_path',
    'custom_domain',
    'favicon_path',
    'social_links',
    'custom_css',
    'branding',
    'suspension_reason',
    'suspended_at',
    'suspended_by',
])]

class Tenant extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'trial',
        'settings' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => TenantStatus::class,
            'type' => TenantType::class,
            'client_tier' => ClientTier::class,
            'trial_ends_at' => 'datetime',
            'social_links' => 'array',
            'custom_css' => 'array',
            'branding' => 'array',
            'suspended_at' => 'datetime',
        ];
    }
    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isFirmBooks(): bool
    {
        return $this->type === TenantType::FirmBooks;
    }

    public function isClientBooks(): bool
    {
        return $this->type === TenantType::ClientBooks;
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::Active);
    }

    public function scopeAccessible(Builder $query): Builder
    {
        return $query->whereIn('status', [TenantStatus::Active, TenantStatus::Trial]);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function isAccessible(): bool
    {
        return $this->status->isAccessible();
    }

    public function isOnTrial(): bool
    {
        return $this->status === TenantStatus::Trial
            && $this->trial_ends_at?->isFuture();
    }

    public function hasExpiredTrial(): bool
    {
        return $this->status === TenantStatus::Trial
            && $this->trial_ends_at?->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'status', 'settings', 'suspension_reason', 'suspended_at', 'suspended_by'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
