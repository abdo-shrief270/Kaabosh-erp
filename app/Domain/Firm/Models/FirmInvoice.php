<?php

declare(strict_types=1);

namespace App\Domain\Firm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('firm_invoices')]
#[Fillable([
    'firm_id',
    'invoice_number',
    'period_start',
    'period_end',
    'plan_tier',
    'base_amount',
    'clients_amount',
    'total_amount',
    'line_items',
    'status',
    'payment_method',
    'payment_reference',
    'paid_at',
    'paid_by_user_id',
])]
class FirmInvoice extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'period_start'    => 'date',
            'period_end'      => 'date',
            'line_items'      => 'array',
            'paid_at'         => 'datetime',
            'base_amount'     => 'integer',
            'clients_amount' => 'integer',
            'total_amount'   => 'integer',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
