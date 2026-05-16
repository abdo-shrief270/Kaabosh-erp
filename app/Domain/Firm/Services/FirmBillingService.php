<?php

declare(strict_types=1);

namespace App\Domain\Firm\Services;

use App\Domain\Firm\Models\Firm;
use App\Domain\Shared\Enums\ClientTier;
use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;

/**
 * Firm billing under Model B (Option C hybrid).
 *
 * Total monthly cost = firm.subscription_tier base price
 *                    + sum(active_client_tenants.tier.price)
 *
 * The firm-books tenant is included free in the base subscription, so we
 * exclude it from the per-client total. Cancelled / suspended client-tenants
 * are also excluded — they don't accrue fees until reactivated.
 */
class FirmBillingService
{
    /**
     * Compute the full billing breakdown for the firm.
     *
     * @return array{
     *     base: array{tier:?string,label:string,price:?int,staff_seats:?int,client_cap:?int},
     *     clients: array<int,array{id:int,name:string,tier:?string,price:int,status:string}>,
     *     totals: array{base:?int,clients:int,monthly_total:?int,active_clients:int,client_cap:?int,overage:int},
     *     currency: string,
     * }
     */
    public function breakdown(Firm $firm): array
    {
        // Pull from the firm's active Subscription row (Model B billing source
        // of truth). Falls back to the firms.subscription_tier column only if
        // no subscription exists — happens for legacy firms before backfill.
        $subscription = \App\Domain\Subscription\Models\Subscription::activeForFirm($firm->id);
        $plan = $subscription?->plan;

        $tierSlug = $plan?->slug ?? $firm->subscription_tier?->value;
        $tier = $tierSlug ? FirmSubscriptionTier::tryFrom($tierSlug) : null;

        $basePrice  = $plan ? (int) $plan->price_monthly : $tier?->monthlyPriceEgp();

        // Effective limits = plan.limits + active add-on boosts. Surfaces
        // what the firm can actually do this cycle, not just the plan baseline.
        $effective = app(\App\Domain\Subscription\Services\AddOnService::class)
            ->getEffectiveLimitsForFirm($firm->id);
        $staffSeats = $effective['max_staff_seats']
            ?? ($plan?->limits['max_staff_seats'] ?? $tier?->staffSeats());
        $clientCap  = $effective['max_client_tenants']
            ?? ($plan?->limits['max_client_tenants'] ?? $tier?->clientTenantCap());

        $clientTenants = Tenant::where('firm_id', $firm->id)
            ->where('type', TenantType::ClientBooks->value)
            ->whereIn('status', [TenantStatus::Active->value, TenantStatus::Trial->value])
            ->orderBy('name')
            ->get();

        $clientLines = $clientTenants->map(function (Tenant $t): array {
            $tier = $t->client_tier instanceof ClientTier ? $t->client_tier : null;
            $price = $tier?->monthlyPriceEgp() ?? 0;
            return [
                'id'     => $t->id,
                'name'   => $t->name,
                'tier'   => $tier?->value,
                'price'  => $price,
                'status' => $t->status?->value ?? '',
            ];
        })->values();

        $clientsTotal = (int) $clientLines->sum('price');
        $activeClients = $clientTenants->count();
        $overage = $clientCap !== null ? max(0, $activeClients - $clientCap) : 0;

        $monthlyTotal = $basePrice !== null ? $basePrice + $clientsTotal : null;

        return [
            'base' => [
                'tier'        => $plan?->slug ?? $tier?->value,
                'label'       => $plan?->name_en ?? $tier?->label() ?? 'No plan',
                'price'       => $basePrice,
                'staff_seats' => $staffSeats,
                'client_cap'  => $clientCap,
            ],
            'clients' => $clientLines->all(),
            'totals'  => [
                'base'           => $basePrice,
                'clients'        => $clientsTotal,
                'monthly_total'  => $monthlyTotal,
                'active_clients' => $activeClients,
                'client_cap'     => $clientCap,
                'overage'        => $overage,
            ],
            'currency' => 'EGP',
        ];
    }
}
