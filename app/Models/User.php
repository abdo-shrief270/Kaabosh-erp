<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Client\Models\Client;
use App\Domain\Firm\Models\Firm;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\TimeTracking\Models\Timer;
use App\Domain\TimeTracking\Models\TimesheetEntry;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'tenant_id',
    'firm_id',
    'firm_role',
    'client_id',
    'name',
    'email',
    'password',
    'phone',
    'role',
    'locale',
    'timezone',
    'ui_preferences',
    'is_active',
    'last_login_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_enabled',
    'password_changed_at',
])]
#[Hidden(['password', 'remember_token', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser
{
    /**
     * Super admins are NOT tenant-scoped.
     * We apply BelongsToTenant manually for tenant-level users only.
     */
    use BelongsToTenant;

    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => 'client',
        'locale' => 'ar',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'firm_role' => FirmRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'password_changed_at' => 'datetime',
            'ui_preferences' => 'array',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Client-tenants this user is explicitly assigned to. Only consulted for
     * Accountant/Viewer firm roles — Owner/Partner/Manager have firm-wide
     * access regardless of this pivot.
     */
    public function assignedTenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'firm_user_tenant',
            'user_id',
            'tenant_id',
        )->withTimestamps();
    }

    /**
     * True when the user can access the given tenant under the current
     * Model-B rules:
     *  - SuperAdmin: always
     *  - Same firm + firm_books type: always (firm's own books visible to all)
     *  - Same firm + firm-wide role (Owner/Partner/Manager): always
     *  - Same firm + Accountant/Viewer: only if pivot row exists
     *  - Different firm: never (unless SuperAdmin)
     *  - Pre-Model-B users with no firm_id: fall back to home tenant equality
     */
    public function canAccessTenant(\App\Domain\Tenant\Models\Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) return true;

        if ($this->firm_id && $tenant->firm_id === $this->firm_id) {
            if ($tenant->isFirmBooks()) return true;
            $role = $this->firm_role;
            if ($role instanceof FirmRole && $role->hasFirmWideAccess()) return true;
            return $this->assignedTenants()->where('tenant_id', $tenant->id)->exists();
        }

        return $this->tenant_id === $tenant->id;
    }

    /**
     * True when this user should skip approval gates on the given tenant.
     * Firm-wide roles bypass implicitly. Accountant/Viewer bypass only when
     * the Owner has explicitly granted it via firm_user_tenant.bypass_approvals.
     */
    public function hasApprovalBypassOn(\App\Domain\Tenant\Models\Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) return true;
        if ($this->firm_id !== $tenant->firm_id) return false;

        $role = $this->firm_role;
        if ($role instanceof FirmRole && $role->hasFirmWideAccess()) return true;

        return \Illuminate\Support\Facades\DB::table('firm_user_tenant')
            ->where('user_id', $this->id)
            ->where('tenant_id', $tenant->id)
            ->where('bypass_approvals', true)
            ->exists();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('role', UserRole::SuperAdmin);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isSuperAdmin() && $this->is_active,
            default => false,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function isTenantUser(): bool
    {
        return $this->role->isTenantLevel();
    }

    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->saveQuietly();
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
