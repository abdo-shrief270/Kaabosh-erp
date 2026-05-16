<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\PaymentStatus;
use App\Domain\Subscription\Enums\PlanSlug;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Grace period in days after current_period_end before expiring a subscription.
     */
    private const GRACE_PERIOD_DAYS = 3;

    /**
     * Firm-scoped — the canonical Model B source of truth. Use this
     * everywhere new code consults subscription state.
     */
    public function getCurrentSubscriptionForFirm(int $firmId): ?Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('firm_id', $firmId)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->orderByRaw('CASE
                WHEN status = ? THEN 1
                WHEN status = ? THEN 2
                WHEN status = ? THEN 3
                ELSE 4
            END', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->latest('id')
            ->first();
    }

    /**
     * Get the current active/accessible subscription for a tenant.
     * Eager loads the plan relationship.
     * @deprecated Use getCurrentSubscriptionForFirm — Model B is firm-scoped
     */
    public function getCurrentSubscription(?int $tenantId = null): ?Subscription
    {
        $tenantId ??= (int) app('tenant.id');

        return Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->orderByRaw('CASE
                WHEN status = ? THEN 1
                WHEN status = ? THEN 2
                WHEN status = ? THEN 3
                ELSE 4
            END', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->first();
    }

    /**
     * Start a trial subscription for a tenant.
     *
     * @throws ValidationException
     */
    public function startTrial(int $tenantId, ?int $planId = null): Subscription
    {
        return DB::transaction(function () use ($tenantId, $planId): Subscription {
            // Check tenant doesn't already have an active subscription
            $existing = Subscription::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', [
                    SubscriptionStatus::Trial,
                    SubscriptionStatus::Active,
                    SubscriptionStatus::PastDue,
                ])
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'subscription' => [
                        'This tenant already has an active subscription.',
                        'هذا المستأجر لديه اشتراك نشط بالفعل.',
                    ],
                ]);
            }

            // Default to free_trial plan if no plan specified
            if ($planId) {
                $plan = Plan::query()->findOrFail($planId);
            } else {
                $plan = Plan::query()->where('slug', PlanSlug::FreeTrial->value)->firstOrFail();
            }

            return Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Trial,
                'billing_cycle' => 'monthly',
                'price' => '0.00',
                'currency' => $plan->currency,
                'trial_ends_at' => now()->addDays($plan->trial_days),
                'current_period_start' => now()->toDateString(),
                'current_period_end' => now()->addDays($plan->trial_days)->toDateString(),
            ]);
        });
    }

    /**
     * Subscribe a tenant to a plan (new subscription or upgrade from trial/expired).
     *
     * @throws ValidationException
     */
    public function subscribe(
        int $tenantId,
        int $planId,
        string $billingCycle = 'monthly',
        string $gateway = 'paymob',
    ): Subscription {
        return DB::transaction(function () use ($tenantId, $planId, $billingCycle, $gateway): Subscription {
            $plan = Plan::query()->findOrFail($planId);
            $price = $plan->priceForCycle($billingCycle);
            $gatewayEnum = PaymentGateway::from($gateway);

            $periodEnd = match ($billingCycle) {
                'annual' => now()->addYear()->toDateString(),
                default => now()->addMonth()->toDateString(),
            };

            $existing = Subscription::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', [
                    SubscriptionStatus::Trial,
                    SubscriptionStatus::Active,
                    SubscriptionStatus::PastDue,
                ])
                ->first();

            if ($existing) {
                // Trial → convert to active
                if ($existing->status === SubscriptionStatus::Trial) {
                    $existing->update([
                        'plan_id' => $plan->id,
                        'status' => SubscriptionStatus::Active,
                        'billing_cycle' => $billingCycle,
                        'price' => $price,
                        'gateway' => $gatewayEnum,
                        'current_period_start' => now()->toDateString(),
                        'current_period_end' => $periodEnd,
                        'trial_ends_at' => null,
                    ]);

                    $subscription = $existing->refresh();
                }
                // Active with different plan → use changePlan
                elseif ($existing->status === SubscriptionStatus::Active && $existing->plan_id !== $planId) {
                    return $this->changePlan($existing, $planId, $billingCycle);
                } else {
                    // Already active on same plan, just return
                    return $existing;
                }
            } else {
                // Check for cancelled/expired to create fresh subscription
                // Expire any old cancelled/expired subscriptions first
                Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired])
                    ->update(['status' => SubscriptionStatus::Expired]);

                $subscription = Subscription::query()->create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Active,
                    'billing_cycle' => $billingCycle,
                    'price' => $price,
                    'currency' => $plan->currency,
                    'gateway' => $gatewayEnum,
                    'current_period_start' => now()->toDateString(),
                    'current_period_end' => $periodEnd,
                ]);
            }

            // Create initial pending payment record
            SubscriptionPayment::query()->create([
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'amount' => $price,
                'currency' => $subscription->currency,
                'status' => PaymentStatus::Pending,
                'gateway' => $gatewayEnum,
                'billing_period_start' => $subscription->current_period_start,
                'billing_period_end' => $subscription->current_period_end,
            ]);

            return $subscription->load('plan');
        });
    }

    /**
     * Cancel a subscription. It remains active until current_period_end (grace period).
     */
    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $subscription->refresh();
    }

    /**
     * Expire a subscription immediately.
     */
    public function expire(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'expires_at' => now(),
        ]);

        return $subscription->refresh();
    }

    /**
     * Renew an existing subscription for the next billing period.
     *
     * @throws ValidationException
     */
    public function renew(Subscription $subscription): Subscription
    {
        if (! $subscription->status->canRenew()) {
            throw ValidationException::withMessages([
                'subscription' => [
                    "Cannot renew a subscription with status '{$subscription->status->label()}'.",
                    "لا يمكن تجديد اشتراك بحالة '{$subscription->status->labelAr()}'.",
                ],
            ]);
        }

        return DB::transaction(function () use ($subscription): Subscription {
            $periodStart = $subscription->current_period_end ?? now()->toDateString();
            $periodEnd = match ($subscription->billing_cycle) {
                'annual' => $periodStart instanceof \DateTimeInterface
                    ? $periodStart->copy()->addYear()->toDateString()
                    : now()->parse($periodStart)->addYear()->toDateString(),
                default => $periodStart instanceof \DateTimeInterface
                    ? $periodStart->copy()->addMonth()->toDateString()
                    : now()->parse($periodStart)->addMonth()->toDateString(),
            };

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'current_period_start' => $periodStart instanceof \DateTimeInterface
                    ? $periodStart->toDateString()
                    : $periodStart,
                'current_period_end' => $periodEnd,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'expires_at' => null,
            ]);

            // Create pending payment for the new period
            SubscriptionPayment::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'amount' => $subscription->price,
                'currency' => $subscription->currency,
                'status' => PaymentStatus::Pending,
                'gateway' => $subscription->gateway,
                'billing_period_start' => $subscription->current_period_start,
                'billing_period_end' => $subscription->current_period_end,
            ]);

            return $subscription->refresh()->load('plan');
        });
    }

    /**
     * Change the plan on an existing subscription.
     * Upgrades (higher price) apply immediately; downgrades apply at next renewal.
     *
     * @throws ValidationException
     */
    public function changePlan(
        Subscription $subscription,
        int $newPlanId,
        ?string $billingCycle = null,
    ): Subscription {
        return DB::transaction(function () use ($subscription, $newPlanId, $billingCycle): Subscription {
            $newPlan = Plan::query()->findOrFail($newPlanId);
            $cycle = $billingCycle ?? $subscription->billing_cycle;
            $newPrice = $newPlan->priceForCycle($cycle);
            $isUpgrade = bccomp((string) $newPrice, (string) $subscription->price, 2) > 0;

            if ($isUpgrade) {
                // Upgrade: apply immediately
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'price' => $newPrice,
                    'billing_cycle' => $cycle,
                ]);
            } else {
                // Downgrade: schedule for next renewal via metadata
                $metadata = $subscription->metadata ?? [];
                $metadata['pending_downgrade'] = [
                    'plan_id' => $newPlan->id,
                    'price' => $newPrice,
                    'billing_cycle' => $cycle,
                    'scheduled_at' => now()->toIso8601String(),
                ];

                $subscription->update([
                    'metadata' => $metadata,
                ]);
            }

            return $subscription->refresh()->load('plan');
        });
    }

    /**
     * Handle a successful payment completion.
     */
    public function handlePaymentCompleted(SubscriptionPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->update([
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            $subscription = $payment->subscription;

            if (! $subscription) {
                return;
            }

            // Ensure subscription is active
            if ($subscription->status !== SubscriptionStatus::Active) {
                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                ]);
            }

            // Set period dates from payment if subscription doesn't have them
            if ($subscription->current_period_start === null && $payment->billing_period_start) {
                $subscription->update([
                    'current_period_start' => $payment->billing_period_start,
                    'current_period_end' => $payment->billing_period_end,
                ]);
            }

            // Apply pending downgrade if any
            $metadata = $subscription->metadata ?? [];
            if (isset($metadata['pending_downgrade'])) {
                $downgrade = $metadata['pending_downgrade'];
                unset($metadata['pending_downgrade']);

                $subscription->update([
                    'plan_id' => $downgrade['plan_id'],
                    'price' => $downgrade['price'],
                    'billing_cycle' => $downgrade['billing_cycle'],
                    'metadata' => $metadata ?: null,
                ]);
            }
        });
    }

    /**
     * Handle a failed payment.
     */
    public function handlePaymentFailed(SubscriptionPayment $payment, string $reason): void
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        $subscription = $payment->subscription;

        if ($subscription && $subscription->status === SubscriptionStatus::Active) {
            $subscription->update([
                'status' => SubscriptionStatus::PastDue,
            ]);
        }

        // Future: send notification, retry logic
    }

    /**
     * Expire all trial subscriptions that have passed their trial end date.
     * Intended to be called by the scheduler.
     *
     * @return int Number of expired trials.
     */
    public function checkExpiredTrials(): int
    {
        $expiredTrials = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($expiredTrials as $subscription) {
            $this->expire($subscription);
        }

        return $expiredTrials->count();
    }

    /**
     * Expire subscriptions whose current period has ended beyond the grace period.
     * Intended to be called by the scheduler.
     *
     * @return int Number of expired subscriptions.
     */
    public function checkExpiredSubscriptions(): int
    {
        $cutoff = now()->subDays(self::GRACE_PERIOD_DAYS);

        $expired = Subscription::withoutGlobalScopes()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $cutoff)
            ->get();

        foreach ($expired as $subscription) {
            $this->expire($subscription);
        }

        return $expired->count();
    }
}
