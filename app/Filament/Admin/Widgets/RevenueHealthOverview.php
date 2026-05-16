<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueHealthOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Revenue Health';

    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            $this->churnStat(),
            $this->trialConversionStat(),
            $this->pastDueStat(),
            $this->paymentFailureStat(),
            $this->refundStat(),
        ];
    }

    /** Count of subscriptions currently in PastDue — a direct alert on revenue at risk. */
    private function pastDueStat(): Stat
    {
        $count = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('status', SubscriptionStatus::PastDue)
            ->count();

        return Stat::make('Past-due subs', (string) $count)
            ->description($count === 0 ? 'No past-due subscriptions' : 'Needs dunning follow-up')
            ->descriptionIcon('heroicon-m-exclamation-circle')
            ->color($count > 0 ? 'danger' : 'success');
    }

    /** Approximates monthly churn: subs cancelled in last 30d / subs active at window start. */
    private function churnStat(): Stat
    {
        $windowStart = now()->subDays(30);

        $cancelledInWindow = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('status', SubscriptionStatus::Cancelled)
            ->whereNotNull('cancelled_at')
            ->where('cancelled_at', '>=', $windowStart)
            ->count();

        // Active population at start of window = any sub created before windowStart and not yet cancelled by then.
        $activeAtStart = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('created_at', '<', $windowStart)
            ->where(function ($q) use ($windowStart): void {
                $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>=', $windowStart);
            })
            ->count();

        if ($activeAtStart === 0) {
            return Stat::make('Churn rate (30d)', '—')
                ->description('No active subscriptions at window start')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('gray');
        }

        $rate = ($cancelledInWindow / $activeAtStart) * 100;
        $color = $rate > 5 ? 'danger' : ($rate > 2 ? 'warning' : 'success');

        return Stat::make('Churn rate (30d)', number_format($rate, 1).'%')
            ->description("{$cancelledInWindow} of {$activeAtStart} cancelled")
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color($color);
    }

    /** Approximates trial→paid conversion: recently-activated subs that had a trial_ends_at before update. */
    private function trialConversionStat(): Stat
    {
        $windowStart = now()->subDays(30);

        $converted = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->where('status', SubscriptionStatus::Active)
            ->where('updated_at', '>=', $windowStart)
            ->whereNotNull('trial_ends_at')
            ->whereColumn('trial_ends_at', '<=', 'updated_at')
            ->count();

        // Denominator: trials whose trial_ends_at fell inside the window (ended, converted or not).
        $trialsEnded = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$windowStart, now()])
            ->count();

        if ($trialsEnded === 0) {
            return Stat::make('Trial → Active (30d)', '—')
                ->description('No trials ended in window')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('gray');
        }

        $rate = ($converted / $trialsEnded) * 100;
        $color = $rate > 20 ? 'success' : ($rate > 10 ? 'warning' : 'danger');

        return Stat::make('Trial → Active (30d)', number_format($rate, 1).'%')
            ->description("{$converted} of {$trialsEnded} trials converted")
            ->descriptionIcon('heroicon-m-arrow-path')
            ->color($color);
    }

    /** Approximates gateway reliability: failed payments / all payments created in last 30d. */
    private function paymentFailureStat(): Stat
    {
        $windowStart = now()->subDays(30);

        $failed = SubscriptionPayment::query()
            ->withoutGlobalScope('tenant')
            ->whereNotNull('failed_at')
            ->where('failed_at', '>=', $windowStart)
            ->count();

        $total = SubscriptionPayment::query()
            ->withoutGlobalScope('tenant')
            ->where('created_at', '>=', $windowStart)
            ->count();

        if ($total === 0) {
            return Stat::make('Payment failures (30d)', '—')
                ->description('No payments in window')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray');
        }

        $rate = ($failed / $total) * 100;
        $color = $rate > 10 ? 'danger' : ($rate > 3 ? 'warning' : 'success');

        return Stat::make('Payment failures (30d)', number_format($rate, 1).'%')
            ->description("{$failed} of {$total} failed")
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($color);
    }

    /** Approximates customer dissatisfaction / disputes: refunds issued / paid in last 30d. */
    private function refundStat(): Stat
    {
        $windowStart = now()->subDays(30);

        $refunded = SubscriptionPayment::query()
            ->withoutGlobalScope('tenant')
            ->whereNotNull('refunded_at')
            ->where('refunded_at', '>=', $windowStart)
            ->count();

        $paid = SubscriptionPayment::query()
            ->withoutGlobalScope('tenant')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $windowStart)
            ->count();

        if ($paid === 0) {
            return Stat::make('Refund rate (30d)', '—')
                ->description('No paid payments in window')
                ->descriptionIcon('heroicon-m-receipt-refund')
                ->color('gray');
        }

        $rate = ($refunded / $paid) * 100;
        $color = $rate > 5 ? 'danger' : ($rate > 2 ? 'warning' : 'success');

        return Stat::make('Refund rate (30d)', number_format($rate, 1).'%')
            ->description("{$refunded} of {$paid} refunded")
            ->descriptionIcon('heroicon-m-receipt-refund')
            ->color($color);
    }
}
