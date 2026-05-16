<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Platform Overview';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants = Tenant::query()->count();

        $activeSubscriptions = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial])
            ->count();

        $mrr = (float) Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('status', SubscriptionStatus::Active)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price_monthly');

        $trialsEnding = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        $activeUsers7d = User::query()
            ->withoutGlobalScope('tenant')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Total Tenants', (string) $totalTenants)
                ->description('All registered tenants')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Active Subscriptions', (string) $activeSubscriptions)
                ->description('Active + trialing')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('MRR', 'EGP '.number_format($mrr, 2))
                ->description('Monthly Recurring Revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('ARR', 'EGP '.number_format($mrr * 12, 2))
                ->description('Annual Recurring Revenue (MRR × 12)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Trials Ending (7d)', (string) $trialsEnding)
                ->description('Trials expiring within a week')
                ->descriptionIcon('heroicon-m-clock')
                ->color($trialsEnding > 0 ? 'warning' : 'gray'),

            Stat::make('Active Users (7d)', (string) $activeUsers7d)
                ->description('Logged in within last 7 days')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($activeUsers7d > 0 ? 'success' : 'gray'),
        ];
    }
}
