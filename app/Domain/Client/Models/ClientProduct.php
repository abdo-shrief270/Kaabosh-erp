<?php

declare(strict_types=1);

namespace App\Domain\Client\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Per-client billable item used to speed up invoice creation. See migration
 * `create_client_products_table` for the rationale.
 *
 * Tenant scoping is enforced by the `BelongsToTenant` trait — every query
 * picks up `WHERE tenant_id = app('tenant.id')` automatically. The unique
 * constraint on (tenant_id, client_id, name) prevents duplicate entries for
 * the same client; the controller catches `QueryException` and surfaces a
 * 422 with a localized message.
 */
#[Table('client_products')]
#[Fillable([
    'tenant_id',
    'client_id',
    'name',
    'description',
    'unit_price',
    'default_vat_rate',
    'default_account_id',
    'is_active',
    'last_used_at',
])]
class ClientProduct extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'default_vat_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }


    /** Active items only — used by the invoice line picker. */
    public function scopeActive(Builder $q): void
    {
        $q->where('is_active', true);
    }

    /** Order by most-recently-used first, then name. The line picker shows
     *  this order so the items the user typically bills surface at the top. */
    public function scopeRecentFirst(Builder $q): void
    {
        $q->orderByDesc('last_used_at')->orderBy('name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'unit_price', 'default_vat_rate', 'default_account_id', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
