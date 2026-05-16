<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Domain\Tenant\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Platform-wide KPIs.
     *
     * @return array<string, mixed>
     */
    public function getKpis(): array
    {
        $tenantCounts = Tenant::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $tenantsActive = $tenantCounts[TenantStatus::Active->value] ?? 0;
        $tenantsTrial = $tenantCounts[TenantStatus::Trial->value] ?? 0;
        $tenantsSuspended = $tenantCounts[TenantStatus::Suspended->value] ?? 0;
        $tenantsCancelled = $tenantCounts[TenantStatus::Cancelled->value] ?? 0;
        $tenantsTotal = array_sum($tenantCounts);

        // MRR from active subscriptions
        $mrr = (float) Subscription::withoutGlobalScopes()
            ->whereIn('status', ['active', 'trial'])
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'monthly' THEN price WHEN billing_cycle = 'annual' THEN price / 12 ELSE 0 END) as mrr")
            ->value('mrr');

        $totalCollected = (float) SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'completed')
            ->sum('amount');

        $newSignupsThisMonth = Tenant::query()
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        // Churn: cancelled/expired in last 30 days
        $churnedLast30 = Subscription::withoutGlobalScopes()
            ->whereIn('status', ['cancelled', 'expired'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $activeSubsCount = Subscription::withoutGlobalScopes()
            ->whereIn('status', ['active', 'trial'])
            ->count();

        $churnRate = $activeSubsCount > 0 ? round(($churnedLast30 / ($activeSubsCount + $churnedLast30)) * 100, 2) : 0;

        // Subscriptions by plan
        $subscriptionsByPlan = Subscription::withoutGlobalScopes()
            ->whereIn('status', ['active', 'trial'])
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('plans.name_ar', 'plans.name_en', DB::raw('COUNT(*) as count'))
            ->groupBy('plans.name_ar', 'plans.name_en')
            ->get();

        return [
            'tenants' => [
                'total' => $tenantsTotal,
                'active' => $tenantsActive,
                'trial' => $tenantsTrial,
                'suspended' => $tenantsSuspended,
                'cancelled' => $tenantsCancelled,
            ],
            'revenue' => [
                'mrr' => round($mrr, 2),
                'arr' => round($mrr * 12, 2),
                'total_collected' => $totalCollected,
            ],
            'new_signups_this_month' => $newSignupsThisMonth,
            'churn_rate' => $churnRate,
            'subscriptions_by_plan' => $subscriptionsByPlan,
        ];
    }

    /**
     * Monthly revenue for the last N months.
     *
     * @return array<int, array{month: string, revenue: float}>
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        return SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'completed')
            ->where('paid_at', '>=', $startDate)
            ->selectRaw("TO_CHAR(paid_at, 'YYYY-MM') as month, SUM(amount) as revenue")
            ->groupBy(DB::raw("TO_CHAR(paid_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'revenue' => (float) $row->revenue,
            ])
            ->toArray();
    }

    /**
     * Revenue breakdown by plan.
     *
     * @return array<int, array{plan: string, revenue: float, count: int}>
     */
    public function getRevenueByPlan(): array
    {
        return SubscriptionPayment::withoutGlobalScopes()
            ->where('subscription_payments.status', 'completed')
            ->join('subscriptions', 'subscription_payments.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                'plans.name_ar',
                'plans.name_en',
                DB::raw('SUM(subscription_payments.amount) as revenue'),
                DB::raw('COUNT(DISTINCT subscriptions.id) as subscription_count'),
            )
            ->groupBy('plans.name_ar', 'plans.name_en')
            ->get()
            ->map(fn ($row) => [
                'plan_ar' => $row->name_ar,
                'plan_en' => $row->name_en,
                'revenue' => (float) $row->revenue,
                'subscription_count' => (int) $row->subscription_count,
            ])
            ->toArray();
    }
}
