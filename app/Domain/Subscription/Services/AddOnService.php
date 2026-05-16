<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Domain\Subscription\Enums\SubscriptionAddOnStatus;
use App\Domain\Subscription\Models\AddOn;
use App\Domain\Subscription\Models\AddOnCredit;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manages add-on lifecycle: catalog browsing, purchase, cancellation, and
 * effective-limit calculation merging plan + active add-on boosts.
 *
 * Add-ons sit beside the subscription, not inside it — a tenant can have
 * many active add-ons of different types stacked on top of one plan.
 */
class AddOnService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    // ──────────────────────────────────────
    // Catalog
    // ──────────────────────────────────────

    /**
     * Public catalog of active add-ons, ordered for display.
     */
    public function catalog(): Collection
    {
        return AddOn::query()->active()->ordered()->get();
    }

    public function findBySlug(string $slug): ?AddOn
    {
        return AddOn::query()->where('slug', $slug)->first();
    }

    // ──────────────────────────────────────
    // Per-tenant queries
    // ──────────────────────────────────────

    /**
     * Active add-ons attached to a firm's current subscription. Canonical
     * Model B reader — every firm-facing surface uses this.
     */
    public function activeForFirm(int $firmId): Collection
    {
        $subscription = $this->subscriptionService->getCurrentSubscriptionForFirm($firmId);
        if (! $subscription) return new Collection();

        return SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->with('addOn')
            ->where('firm_id', $firmId)
            ->where('subscription_id', $subscription->id)
            ->active()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Purchase an add-on for a firm. Mirrors purchase() but writes firm_id
     * instead of tenant_id and resolves the subscription via firm scope.
     *
     * @param array{billing_cycle?: string, quantity?: int, gateway?: string, gateway_payment_id?: string} $opts
     * @throws ValidationException
     */
    public function purchaseForFirm(AddOn $addOn, array $opts, int $firmId): SubscriptionAddOn
    {
        if (! $addOn->is_active) {
            throw ValidationException::withMessages([
                'add_on' => ['This add-on is no longer available.'],
            ]);
        }

        $subscription = $this->subscriptionService->getCurrentSubscriptionForFirm($firmId);
        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active firm subscription. Subscribe to a plan before purchasing add-ons.'],
            ]);
        }

        $cycle    = $this->resolveCycle($addOn, $opts['billing_cycle'] ?? null);
        $quantity = max(1, (int) ($opts['quantity'] ?? 1));
        $unitPrice = Money::of($addOn->priceForCycle($cycle));

        $gateway = $opts['gateway'] ?? null;
        $startPending = $gateway && in_array($gateway, self::ONLINE_GATEWAYS, true);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($addOn, $subscription, $cycle, $quantity, $unitPrice, $opts, $firmId, $startPending): SubscriptionAddOn {
            [$periodStart, $periodEnd, $expiresAt] = $this->computeWindow($subscription, $cycle);

            $status = $startPending
                ? \App\Domain\Subscription\Enums\SubscriptionAddOnStatus::Pending->value
                : \App\Domain\Subscription\Enums\SubscriptionAddOnStatus::Active->value;

            /** @var SubscriptionAddOn $row */
            $row = SubscriptionAddOn::query()->create([
                'firm_id'              => $firmId,
                'tenant_id'            => null,
                'subscription_id'      => $subscription->id,
                'add_on_id'            => $addOn->id,
                'quantity'             => $quantity,
                'status'               => $status,
                'billing_cycle'        => $cycle->value,
                'price'                => $unitPrice,
                'currency'             => $addOn->currency ?? $subscription->currency ?? 'EGP',
                'current_period_start' => $periodStart,
                'current_period_end'   => $periodEnd,
                'expires_at'           => $expiresAt,
                'gateway'              => $opts['gateway'] ?? null,
                'gateway_payment_id'   => $opts['gateway_payment_id'] ?? null,
            ]);

            // Credit packs only seed their wallet once payment is confirmed.
            // Firm-scoped credit rows mirror the pattern: firm_id set, tenant_id null.
            if (! $startPending
                && $addOn->type === \App\Domain\Subscription\Enums\AddOnType::CreditPack
                && $addOn->credit_kind
            ) {
                \App\Domain\Subscription\Models\AddOnCredit::query()->create([
                    'firm_id'                => $firmId,
                    'tenant_id'              => null,
                    'subscription_add_on_id' => $row->id,
                    'kind'                   => $addOn->credit_kind,
                    'quantity_total'         => (int) ($addOn->credit_quantity ?? 0) * $quantity,
                    'quantity_used'          => 0,
                    'expires_at'             => $expiresAt,
                ]);
            }

            return $row->fresh(['addOn', 'credits']);
        });
    }

    /**
     * Effective limits for a firm = plan limits + sum of active add-on boosts.
     * Used by FirmBillingService::breakdown so the breakdown reflects what
     * the firm can actually do this cycle.
     *
     * @return array<string,int|null>
     */
    public function getEffectiveLimitsForFirm(int $firmId): array
    {
        $subscription = $this->subscriptionService->getCurrentSubscriptionForFirm($firmId);
        if (! $subscription) return [];

        $planLimits = (array) ($subscription->plan?->limits ?? []);

        $boosts = $this->activeForFirm($firmId)
            ->reduce(function (array $carry, SubscriptionAddOn $row): array {
                $boost = (array) ($row->addOn?->boost ?? []);
                foreach ($boost as $key => $val) {
                    if ($val === null) { $carry[$key] = null; continue; }
                    $carry[$key] = ($carry[$key] ?? 0) + ((int) $val * (int) $row->quantity);
                }
                return $carry;
            }, []);

        $merged = $planLimits;
        foreach ($boosts as $key => $val) {
            if ($val === null) { $merged[$key] = null; continue; }
            $base = $merged[$key] ?? 0;
            $merged[$key] = $base === null ? null : $base + $val;
        }
        return $merged;
    }

    /**
     * Active add-ons attached to the current tenant's subscription.
     * @deprecated use activeForFirm — Model B subscriptions are firm-scoped
     */
    public function activeForTenant(?int $tenantId = null): Collection
    {
        $tenantId ??= (int) app('tenant.id');

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (! $subscription) {
            return new Collection();
        }

        return SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->with('addOn')
            ->where('tenant_id', $tenantId)
            ->where('subscription_id', $subscription->id)
            ->active()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * All add-ons (any status) for the tenant's current subscription —
     * used by the "your add-ons" UI tab to also show recently cancelled ones.
     */
    public function allForTenant(?int $tenantId = null): Collection
    {
        $tenantId ??= (int) app('tenant.id');

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (! $subscription) {
            return new Collection();
        }

        return SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->with('addOn')
            ->where('tenant_id', $tenantId)
            ->where('subscription_id', $subscription->id)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'cancelled' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();
    }

    // ──────────────────────────────────────
    // Purchase
    // ──────────────────────────────────────

    /**
     * Online gateways that should park the row in `pending` until the
     * webhook confirms capture. Bank transfers stay synchronous — they're
     * confirmed manually by an admin so we can't gate on a webhook.
     */
    private const ONLINE_GATEWAYS = ['paymob', 'fawry'];

    /**
     * Purchase an add-on for the current tenant's subscription.
     *
     * For boost/feature: creates an active SubscriptionAddOn aligned to the
     * subscription's current period.
     * For credit_pack: creates the SubscriptionAddOn AND seeds an
     * AddOnCredit row with the granted balance (× quantity).
     *
     * Online payments (paymob/fawry) start as `pending` and don't grant
     * access until the gateway webhook calls `confirmPayment`. Bank-transfer
     * purchases activate immediately because there's no async signal.
     * Stacking is allowed — buying the same boost twice creates a second
     * row.
     *
     * @param  array{billing_cycle?: string, quantity?: int, gateway?: string, gateway_payment_id?: string}  $opts
     *
     * @throws ValidationException
     */
    public function purchase(AddOn $addOn, array $opts = [], ?int $tenantId = null): SubscriptionAddOn
    {
        $tenantId ??= (int) app('tenant.id');

        if (! $addOn->is_active) {
            throw ValidationException::withMessages([
                'add_on' => ['This add-on is no longer available.'],
            ]);
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription. Subscribe to a plan before purchasing add-ons.'],
            ]);
        }

        $cycle = $this->resolveCycle($addOn, $opts['billing_cycle'] ?? null);
        $quantity = max(1, (int) ($opts['quantity'] ?? 1));

        $unitPrice = Money::of($addOn->priceForCycle($cycle));

        $gateway = $opts['gateway'] ?? null;
        $startPending = $gateway && in_array($gateway, self::ONLINE_GATEWAYS, true);

        return DB::transaction(function () use ($addOn, $subscription, $cycle, $quantity, $unitPrice, $opts, $tenantId, $startPending): SubscriptionAddOn {
            [$periodStart, $periodEnd, $expiresAt] = $this->computeWindow($subscription, $cycle);

            $status = $startPending
                ? SubscriptionAddOnStatus::Pending->value
                : SubscriptionAddOnStatus::Active->value;

            /** @var SubscriptionAddOn $row */
            $row = SubscriptionAddOn::query()->create([
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'add_on_id' => $addOn->id,
                'quantity' => $quantity,
                'status' => $status,
                'billing_cycle' => $cycle->value,
                'price' => $unitPrice,
                'currency' => $addOn->currency ?? $subscription->currency ?? 'EGP',
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'expires_at' => $expiresAt,
                'gateway' => $opts['gateway'] ?? null,
                'gateway_payment_id' => $opts['gateway_payment_id'] ?? null,
            ]);

            // Credit packs only seed their wallet once payment is confirmed —
            // a pending pack contributes 0 balance until the webhook fires.
            if (! $startPending && $addOn->type === AddOnType::CreditPack && $addOn->credit_kind) {
                AddOnCredit::query()->create([
                    'tenant_id' => $tenantId,
                    'subscription_add_on_id' => $row->id,
                    'kind' => $addOn->credit_kind,
                    'quantity_total' => (int) ($addOn->credit_quantity ?? 0) * $quantity,
                    'quantity_used' => 0,
                    'expires_at' => $expiresAt,
                ]);
            }

            return $row->fresh(['addOn', 'credits']);
        });
    }

    /**
     * Activate a pending add-on after the gateway confirms capture.
     *
     * Idempotent: re-calling on an already-active row is a no-op (the gateway
     * sometimes retries webhooks). Returns null when no row matches the
     * gateway order id — the caller decides whether to log or hard-fail.
     */
    public function confirmPayment(string $gatewayPaymentId): ?SubscriptionAddOn
    {
        /** @var SubscriptionAddOn|null $row */
        $row = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('gateway_payment_id', $gatewayPaymentId)
            ->with('addOn')
            ->first();

        if (! $row) {
            return null;
        }

        // Already-confirmed rows are returned unchanged.
        if ($row->status !== SubscriptionAddOnStatus::Pending) {
            return $row;
        }

        return DB::transaction(function () use ($row): SubscriptionAddOn {
            $row->update([
                'status' => SubscriptionAddOnStatus::Active->value,
            ]);

            $addOn = $row->addOn;
            if ($addOn?->type === AddOnType::CreditPack && $addOn->credit_kind) {
                AddOnCredit::query()->create([
                    'tenant_id' => $row->tenant_id,
                    'subscription_add_on_id' => $row->id,
                    'kind' => $addOn->credit_kind,
                    'quantity_total' => (int) ($addOn->credit_quantity ?? 0) * $row->quantity,
                    'quantity_used' => 0,
                    'expires_at' => $row->expires_at,
                ]);
            }

            return $row->fresh(['addOn', 'credits']);
        });
    }

    /**
     * Manually flip a pending row to active and seed credits if applicable.
     *
     * Use cases:
     *   - Bank-transfer / cheque purchases where there's no gateway webhook.
     *   - Super-admin override when a gateway webhook fails or is missing
     *     (e.g. local dev without real Paymob set up).
     *
     * Idempotent: re-running on an already-active row is a no-op.
     */
    public function activate(SubscriptionAddOn $row): SubscriptionAddOn
    {
        if ($row->status !== SubscriptionAddOnStatus::Pending) {
            return $row;
        }

        return DB::transaction(function () use ($row): SubscriptionAddOn {
            $row->update(['status' => SubscriptionAddOnStatus::Active->value]);

            $addOn = $row->addOn;
            if ($addOn?->type === AddOnType::CreditPack && $addOn->credit_kind) {
                // Mirror the credit-seeding logic in confirmPayment() so a
                // manually-activated credit pack delivers its balance.
                AddOnCredit::query()->create([
                    'tenant_id' => $row->tenant_id,
                    'subscription_add_on_id' => $row->id,
                    'kind' => $addOn->credit_kind,
                    'quantity_total' => (int) ($addOn->credit_quantity ?? 0) * $row->quantity,
                    'quantity_used' => 0,
                    'expires_at' => $row->expires_at,
                ]);
            }

            return $row->fresh(['addOn', 'credits']);
        });
    }

    /**
     * Mark a pending add-on as failed after the gateway reports a rejected
     * payment. Idempotent against retries.
     */
    public function failPayment(string $gatewayPaymentId, ?string $reason = null): ?SubscriptionAddOn
    {
        /** @var SubscriptionAddOn|null $row */
        $row = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('gateway_payment_id', $gatewayPaymentId)
            ->first();

        if (! $row) {
            return null;
        }

        if ($row->status !== SubscriptionAddOnStatus::Pending) {
            return $row;
        }

        $row->update([
            'status' => SubscriptionAddOnStatus::Failed->value,
            'metadata' => array_merge($row->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);

        return $row->fresh();
    }

    // ──────────────────────────────────────
    // Cancellation
    // ──────────────────────────────────────

    /**
     * Cancel an add-on at the end of its current period (no proration refund).
     * Boost/feature add-ons remain effective until current_period_end and
     * are then auto-expired by the renewal cron. For one-time credit packs,
     * cancellation just stops auto-renewal at the catalog level — the
     * remaining credits are retained until consumed or expired.
     */
    public function cancelAtPeriodEnd(SubscriptionAddOn $row): SubscriptionAddOn
    {
        if ($row->status !== SubscriptionAddOnStatus::Active) {
            return $row;
        }

        $row->update([
            'cancel_at_period_end' => true,
            'cancelled_at' => now(),
        ]);

        return $row->fresh();
    }

    /**
     * Hard-cancel — flips status to cancelled and stops further effect.
     * Use sparingly (refunds, fraud, admin override). Period-end cancel
     * is the normal path.
     */
    public function cancelImmediately(SubscriptionAddOn $row): SubscriptionAddOn
    {
        $row->update([
            'status' => SubscriptionAddOnStatus::Cancelled->value,
            'cancelled_at' => now(),
            'expires_at' => now(),
        ]);

        return $row->fresh();
    }

    // ──────────────────────────────────────
    // Limit merge
    // ──────────────────────────────────────

    /**
     * Effective per-resource limits = plan.limits[k] + sum of active boosts.
     *
     * `-1` (unlimited) on either side keeps unlimited — boosts can't shrink
     * a limit, only raise it. Unknown plan keys default to 0.
     *
     * @return array<string, int>
     */
    public function getEffectiveLimits(?int $tenantId = null): array
    {
        $tenantId ??= (int) app('tenant.id');

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);

        $effective = [];
        if ($subscription !== null && $subscription->plan !== null) {
            $limits = $subscription->plan->getAttribute('limits');
            if (is_array($limits)) {
                foreach ($limits as $key => $value) {
                    $effective[(string) $key] = (int) $value;
                }
            }
        }

        if (! $subscription) {
            return $effective;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, SubscriptionAddOn> $boosts */
        $boosts = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subscription_id', $subscription->id)
            ->active()
            ->whereHas('addOn', fn ($q) => $q->where('type', AddOnType::Boost->value))
            ->with('addOn')
            ->get();

        foreach ($boosts as $row) {
            /** @var AddOn|null $addOn */
            $addOn = $row->addOn;
            if ($addOn === null) {
                continue;
            }
            $boost = $addOn->boost ?? [];
            foreach ($boost as $key => $delta) {
                $current = $effective[$key] ?? 0;
                if ($current === -1) {
                    continue; // already unlimited
                }
                $effective[$key] = $current + ((int) $delta * (int) $row->quantity);
            }
        }

        return $effective;
    }

    /**
     * Map of feature_slug => true for any active feature add-on. Merge with
     * plan features at the consumer (PlanFeatureCache) level.
     *
     * @return array<string, bool>
     */
    public function getFeatureUnlocks(?int $tenantId = null): array
    {
        $tenantId ??= (int) app('tenant.id');

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (! $subscription) {
            return [];
        }

        $rows = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subscription_id', $subscription->id)
            ->active()
            ->whereHas('addOn', fn ($q) => $q->where('type', AddOnType::Feature->value))
            ->with('addOn')
            ->get();

        $features = [];
        foreach ($rows as $row) {
            $slug = $row->addOn?->feature_slug;
            if ($slug) {
                $features[$slug] = true;
            }
        }

        return $features;
    }

    /**
     * Active boost contributions per metric, used by the UI to show
     * "10 base + 5 from add-ons" breakdowns.
     *
     * @return array<string, int>
     */
    public function getBoostBreakdown(?int $tenantId = null): array
    {
        $tenantId ??= (int) app('tenant.id');

        $subscription = $this->subscriptionService->getCurrentSubscription($tenantId);
        if (! $subscription) {
            return [];
        }

        $boosts = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subscription_id', $subscription->id)
            ->active()
            ->whereHas('addOn', fn ($q) => $q->where('type', AddOnType::Boost->value))
            ->with('addOn')
            ->get();

        $breakdown = [];
        foreach ($boosts as $row) {
            /** @var AddOn|null $addOn */
            $addOn = $row->addOn;
            if ($addOn === null) {
                continue;
            }
            $boost = $addOn->boost ?? [];
            foreach ($boost as $key => $delta) {
                $breakdown[$key] = ($breakdown[$key] ?? 0) + ((int) $delta * (int) $row->quantity);
            }
        }

        return $breakdown;
    }

    // ──────────────────────────────────────
    // Credit balance
    // ──────────────────────────────────────

    /**
     * Remaining credits for a given kind on the current tenant.
     */
    public function creditBalance(string $kind, ?int $tenantId = null): int
    {
        $tenantId ??= (int) app('tenant.id');

        $rows = AddOnCredit::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->ofKind($kind)
            ->usable()
            ->get(['quantity_total', 'quantity_used']);

        $remaining = 0;
        foreach ($rows as $row) {
            $remaining += $row->remaining();
        }

        return $remaining;
    }

    /**
     * Consume N credits of a given kind, oldest-non-expired pack first.
     * Returns the actual amount consumed (may be less than requested if
     * insufficient balance — the caller decides whether to fail or
     * partially-degrade).
     */
    public function consumeCredits(string $kind, int $quantity, ?int $tenantId = null): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        $tenantId ??= (int) app('tenant.id');

        return DB::transaction(function () use ($kind, $quantity, $tenantId): int {
            $rows = AddOnCredit::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->ofKind($kind)
                ->usable()
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            $consumed = 0;

            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }
                $available = $row->remaining();
                if ($available <= 0) {
                    continue;
                }
                $take = min($available, $remaining);
                $row->update(['quantity_used' => $row->quantity_used + $take]);
                $remaining -= $take;
                $consumed += $take;
            }

            return $consumed;
        });
    }

    /**
     * @return array<string, array{kind: string, balance: int}>
     */
    public function creditSummary(?int $tenantId = null): array
    {
        $tenantId ??= (int) app('tenant.id');

        $kinds = AddOnCredit::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->usable()
            ->groupBy('kind')
            ->pluck('kind');

        $summary = [];
        foreach ($kinds as $kind) {
            $summary[$kind] = [
                'kind' => $kind,
                'balance' => $this->creditBalance($kind, $tenantId),
            ];
        }

        return $summary;
    }

    // ──────────────────────────────────────
    // Renewal / expiry housekeeping
    // ──────────────────────────────────────

    /**
     * Run-once-per-day expiry sweep. Marks active add-ons with
     * cancel_at_period_end=true and a passed period_end as Cancelled, and
     * marks active rows with a passed expires_at as Expired.
     *
     * Returns the number of rows touched, for logging.
     */
    public function expireDue(): int
    {
        $now = now();

        $cancelled = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('status', SubscriptionAddOnStatus::Active->value)
            ->where('cancel_at_period_end', true)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $now->toDateString())
            ->update([
                'status' => SubscriptionAddOnStatus::Cancelled->value,
                'expires_at' => $now,
                'updated_at' => $now,
            ]);

        $expired = SubscriptionAddOn::query()
            ->withoutGlobalScopes()
            ->where('status', SubscriptionAddOnStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => SubscriptionAddOnStatus::Expired->value,
                'updated_at' => $now,
            ]);

        return (int) $cancelled + (int) $expired;
    }

    // ──────────────────────────────────────
    // Internals
    // ──────────────────────────────────────

    private function resolveCycle(AddOn $addOn, ?string $requested): AddOnBillingCycle
    {
        // CreditPack is always once — ignore caller's request to keep the
        // catalog source-of-truth for what's purchasable.
        if ($addOn->type === AddOnType::CreditPack) {
            return AddOnBillingCycle::Once;
        }

        if ($requested) {
            try {
                return AddOnBillingCycle::from($requested);
            } catch (\ValueError) {
                // fall through to catalog default
            }
        }

        return $addOn->billing_cycle ?? AddOnBillingCycle::Monthly;
    }

    /**
     * Align the add-on's period to the parent subscription's window where
     * possible — keeps renewal dates in sync so the tenant sees one bill.
     *
     * Returns date strings (Y-m-d) for the period bounds and a Carbon for
     * `expires_at` when the row is one-time, null otherwise.
     *
     * @return array{0: string|null, 1: string|null, 2: Carbon|null}  [periodStart, periodEnd, expiresAt]
     */
    private function computeWindow(Subscription $subscription, AddOnBillingCycle $cycle): array
    {
        $now = now();

        if ($cycle === AddOnBillingCycle::Once) {
            // Credits expire at the end of the parent subscription period
            // if known, otherwise 1 year out as a safety net.
            $expires = $subscription->current_period_end
                ? Carbon::parse($subscription->current_period_end)->endOfDay()
                : $now->copy()->addYear();

            return [$now->toDateString(), $expires->toDateString(), $expires];
        }

        $start = $subscription->current_period_start
            ? Carbon::parse($subscription->current_period_start)
            : $now->copy()->startOfDay();

        $end = $cycle === AddOnBillingCycle::Annual
            ? $start->copy()->addYear()->subDay()
            : ($subscription->current_period_end
                ? Carbon::parse($subscription->current_period_end)
                : $start->copy()->addMonth()->subDay());

        return [$start->toDateString(), $end->toDateString(), null];
    }
}
