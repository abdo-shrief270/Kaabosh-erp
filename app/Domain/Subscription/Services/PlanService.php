<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PlanService
{
    /**
     * List all plans ordered by sort_order.
     */
    public function listPlans(bool $activeOnly = true): Collection
    {
        return Plan::query()
            ->when($activeOnly, fn ($q) => $q->active())
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();
    }

    /**
     * Find a plan by ID or fail.
     */
    public function getPlan(int $id): Plan
    {
        return Plan::query()->findOrFail($id);
    }

    /**
     * Find a plan by slug or fail.
     */
    public function getPlanBySlug(string $slug): Plan
    {
        return Plan::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Create a new plan.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPlan(array $data): Plan
    {
        return Plan::query()->create($data);
    }

    /**
     * Update an existing plan.
     * Warns (via metadata) if the plan has active subscriptions, but does not block the update.
     *
     * @param  array<string, mixed>  $data
     * @return Plan The updated plan. Check $plan->getMeta('has_active_subscriptions') for warning.
     */
    public function updatePlan(Plan $plan, array $data): Plan
    {
        $hasActiveSubscriptions = $plan->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Trial, SubscriptionStatus::Active])
            ->exists();

        $plan->update($data);

        if ($hasActiveSubscriptions) {
            $plan->setAttribute('_warning', 'This plan has active subscriptions. Changes will not affect existing subscription prices.');
        }

        return $plan->refresh();
    }

    /**
     * Deactivate a plan by setting is_active to false.
     * Prevents deactivation if it is the only active plan remaining.
     *
     * @throws ValidationException
     */
    public function deactivatePlan(Plan $plan): Plan
    {
        if (! $plan->is_active) {
            return $plan;
        }

        $activeCount = Plan::query()->active()->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages([
                'plan' => [
                    'Cannot deactivate the only active plan. At least one plan must remain active.',
                    'لا يمكن تعطيل الخطة الوحيدة النشطة. يجب أن تبقى خطة واحدة نشطة على الأقل.',
                ],
            ]);
        }

        $plan->update(['is_active' => false]);

        return $plan->refresh();
    }
}
