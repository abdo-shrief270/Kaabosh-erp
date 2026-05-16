<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MrrTrendChart extends ChartWidget
{
    protected ?string $heading = 'MRR Trend (12 months)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthEnd = now()->subMonthsNoOverflow($i)->endOfMonth();

            $labels[] = $monthEnd->format('M y');
            $values[] = $this->mrrAt($monthEnd);
        }

        return [
            'datasets' => [
                [
                    'label' => 'MRR (EGP)',
                    'data' => $values,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /** Snapshot MRR at a moment: sum(plan.price_monthly) for subs created ≤ d, not cancelled by d, not trial. */
    private function mrrAt(Carbon $moment): float
    {
        return (float) Subscription::query()
            ->withoutGlobalScope('tenant')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.created_at', '<=', $moment)
            ->where(function ($q) use ($moment): void {
                $q->whereNull('subscriptions.cancelled_at')
                    ->orWhere('subscriptions.cancelled_at', '>', $moment);
            })
            ->where('subscriptions.status', '!=', SubscriptionStatus::Trial->value)
            ->sum('plans.price_monthly');
    }

    protected function getType(): string
    {
        return 'line';
    }
}
