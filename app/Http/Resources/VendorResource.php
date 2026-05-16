<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\AccountsPayable\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vendor JSON shape consumed by the SPA. Mirrors the columns defined in
 * `vendors` migration and exposes the optional financial summary fields
 * (`balance`, `open_bills_count`, `aging_buckets`, `last_payment_at`) that
 * VendorController::show() merges into the model. The `whenHas()` calls
 * keep those fields out of list responses where they are not computed.
 *
 * @mixin Vendor
 */
class VendorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,

            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,

            'tax_id' => $this->tax_id,
            'commercial_register' => $this->commercial_register,
            'vat_registration' => $this->vat_registration,

            'email' => $this->email,
            'phone' => $this->phone,

            'address_ar' => $this->address_ar,
            'address_en' => $this->address_en,
            'city' => $this->city,
            'country' => $this->country,

            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'iban' => $this->iban,
            'swift_code' => $this->swift_code,

            'payment_terms' => $this->payment_terms,
            'credit_limit' => $this->credit_limit,
            'currency' => $this->currency,

            'contacts' => $this->contacts,
            'notes' => $this->notes,
            'is_active' => $this->is_active,

            'bills_count' => $this->whenCounted('bills'),

            // Detail-only enrichment populated by VendorController::show()
            // via setAttribute(). On list responses these are absent —
            // mergeWhen() keeps the keys out so the SPA can detect via
            // `?? null` instead of receiving zeroes that look meaningful.
            ...$this->mergeWhen($this->resource->getAttribute('balance') !== null, [
                'balance' => $this->resource->getAttribute('balance'),
                'open_bills_count' => $this->resource->getAttribute('open_bills_count'),
                'aging_buckets' => $this->resource->getAttribute('aging_buckets'),
                'last_payment_at' => $this->resource->getAttribute('last_payment_at'),
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
