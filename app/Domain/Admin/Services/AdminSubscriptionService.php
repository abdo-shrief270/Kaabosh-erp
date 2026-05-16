<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AdminSubscriptionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Subscription::withoutGlobalScopes()
            ->with(['tenant', 'plan'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['plan_id']), fn ($q) => $q->where('plan_id', $filters['plan_id']))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->whereHas('tenant', function ($tq) use ($filters): void {
                    $tq->where('name', 'ilike', "%{$filters['search']}%");
                })
            )
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create or replace a subscription for a tenant.
     *
     * @param  array<string, mixed>  $data  Must include: tenant_id, plan_id. Optional: billing_cycle, status, trial_ends_at
     *
     * @throws ValidationException
     */
    public function assignToTenant(array $data): Subscription
    {
        $plan = Plan::query()->findOrFail($data['plan_id']);

        // Cancel any existing active subscription
        Subscription::withoutGlobalScopes()
            ->where('tenant_id', $data['tenant_id'])
            ->whereIn('status', ['active', 'trial'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Replaced by admin',
            ]);

        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        $price = $billingCycle === 'annual' ? $plan->price_annual : $plan->price_monthly;
        $status = $data['status'] ?? 'active';

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $data['tenant_id'],
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => $billingCycle,
            'price' => $price,
            'currency' => 'EGP',
            'current_period_start' => now(),
            'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
            'trial_ends_at' => $status === 'trial' ? ($data['trial_ends_at'] ?? now()->addDays(14)) : null,
        ]);

        return $subscription->load(['tenant', 'plan']);
    }

    public function getDetail(Subscription $subscription): Subscription
    {
        return $subscription->load(['tenant', 'plan', 'payments']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function override(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);

        return $subscription->refresh()->load(['tenant', 'plan']);
    }

    /**
     * @throws ValidationException
     */
    public function refund(SubscriptionPayment $payment): SubscriptionPayment
    {
        if ($payment->status !== 'completed' && $payment->status?->value !== 'completed') {
            throw ValidationException::withMessages([
                'payment' => [
                    'Only completed payments can be refunded.',
                    'يمكن استرداد المدفوعات المكتملة فقط.',
                ],
            ]);
        }

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        return $payment->refresh();
    }
}
