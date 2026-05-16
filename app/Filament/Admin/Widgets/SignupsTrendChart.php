<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Tenant\Models\Tenant;
use Filament\Widgets\ChartWidget;

class SignupsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tenant Signups (12 months)';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $start = now()->subMonthsNoOverflow(11)->startOfMonth();

        $rows = Tenant::query()
            ->selectRaw("to_char(created_at, 'YYYY-MM') as ym, COUNT(*) as c")
            ->where('created_at', '>=', $start)
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i);
            $key = $month->format('Y-m');

            $labels[] = $month->format('M y');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'New tenants',
                    'data' => $values,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
