<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\AccountsPayable\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bill JSON shape for the SPA. Mirrors the columns persisted by
 * BillService and includes the vendor + lines + payments relations
 * when loaded. `balance_due` is derived from total - amount_paid.
 *
 * @mixin Bill
 */
class BillResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'vendor_id' => $this->vendor_id,

            'bill_number' => $this->bill_number,
            'date' => $this->date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_label_ar' => $this->status?->labelAr(),
            'status_color' => $this->status?->color(),

            // Money — keep as decimal strings so the SPA can render with bcmath
            // semantics (it casts to Number for display only).
            'subtotal' => $this->subtotal,
            'vat_amount' => $this->vat_amount,
            'wht_amount' => $this->wht_amount,
            'total' => $this->total,
            'amount_paid' => $this->amount_paid,
            'balance_due' => bcsub((string) $this->total, (string) $this->amount_paid, 2),

            'currency' => $this->currency,
            'notes' => $this->notes,

            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by' => $this->cancelled_by,
            'journal_entry_id' => $this->journal_entry_id,

            'lines_count' => $this->whenCounted('lines'),

            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'name_ar' => $this->vendor->name_ar,
                'name_en' => $this->vendor->name_en,
                'currency' => $this->vendor->currency,
            ]),

            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'description' => $line->description,
                'account_id' => $line->account_id,
                'account' => $line->relationLoaded('account') && $line->account ? [
                    'id' => $line->account->id,
                    'code' => $line->account->code ?? null,
                    'name' => $line->account->name_en ?? $line->account->name_ar ?? null,
                ] : null,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'vat_rate' => $line->vat_rate,
                'wht_rate' => $line->wht_rate,
                'line_total' => $line->line_total,
                'vat_amount' => $line->vat_amount,
                'wht_amount' => $line->wht_amount,
                'total' => $line->total,
                'sort_order' => $line->sort_order,
            ])),

            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'payment_date' => $p->payment_date?->toDateString(),
                // PaymentMethod is an enum cast — coerce to its scalar `value`
                // so the SPA gets the snake_case wire form, not "[object]".
                'payment_method' => $p->payment_method?->value ?? null,
                'reference' => $p->reference,
                'check_number' => $p->check_number,
                'notes' => $p->notes,
            ])),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
