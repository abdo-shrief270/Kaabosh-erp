<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\AccountsPayable\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vendor product JSON shape consumed by the SPA's bill-line picker and
 * the vendor detail page's Products tab. Mirrors ClientProductResource
 * with the addition of `default_account` — vendor products carry a
 * preferred GL account so the picker can auto-fill it on the line.
 *
 * @mixin VendorProduct
 */
class VendorProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            // Eager-loaded by the catalog endpoint; absent on per-vendor
            // routes where the vendor is implicit from the URL.
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'name_ar' => $this->vendor->name_ar,
                'name_en' => $this->vendor->name_en,
                'code' => $this->vendor->code,
                'currency' => $this->vendor->currency,
            ]),

            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => $this->unit_price,
            'default_vat_rate' => $this->default_vat_rate,
            'default_account_id' => $this->default_account_id,
            'default_account' => $this->whenLoaded('defaultAccount', fn () => $this->defaultAccount ? [
                'id' => $this->defaultAccount->id,
                'code' => $this->defaultAccount->code,
                'name_ar' => $this->defaultAccount->name_ar,
                'name_en' => $this->defaultAccount->name_en,
            ] : null),

            'is_active' => $this->is_active,
            'last_used_at' => $this->last_used_at?->toIso8601String(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
