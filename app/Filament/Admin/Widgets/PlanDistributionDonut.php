<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PlanDistributionDonut extends ChartWidget
{
    protected ?string $heading = 'Active Subscriptions by Plan';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    /** Stable palette — cycled when there are more plans than entries. */
    private const PALETTE = [
        '#6366f1', // indigo-500
        '#10b981', // emerald-500
        '#f59e0b', // amber-500
        '#0ea5e9', // sky-500
        '#f43f5e', // rose-500
        '#8b5cf6', // violet-500
        '#14b8a6', // teal-500
        '#ef4444', // red-500
    ];

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $rows = Subscription::query()
            ->withoutGlobalScope('tenant')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial])
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('plans.name_en as plan_name', DB::raw('COUNT(*) as c'))
            ->groupBy('plans.id', 'plans.name_en')
            ->orderByDesc('c')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($rows as $i => $row) {
            $labels[] = (string) $row->plan_name;
            $data[] = (int) $row->c;
            $colors[] = self::PALETTE[$i % count(self::PALETTE)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Subscriptions',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
