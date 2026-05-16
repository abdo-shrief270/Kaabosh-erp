<?php

declare(strict_types=1);

namespace App\Domain\Firm\Models;

use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('firms')]
#[Fillable([
    'name',
    'slug',
    'email',
    'phone',
    'tax_id',
    'commercial_register',
    'address',
    'city',
    'status',
    'subscription_tier',
    'subscription_ends_at',
    'settings',
])]
class Firm extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status'   => 'active',
        'settings' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'settings'             => 'array',
            'subscription_ends_at' => 'datetime',
            'subscription_tier'    => FirmSubscriptionTier::class,
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function ownBooks(): HasMany
    {
        return $this->hasMany(Tenant::class)->where('type', TenantType::FirmBooks->value);
    }

    public function clientTenants(): HasMany
    {
        return $this->hasMany(Tenant::class)->where('type', TenantType::ClientBooks->value);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
