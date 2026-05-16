<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Filament\Widgets\ChartWidget;

class TenantStatusDonut extends ChartWidget
{
    protected ?string $heading = 'Tenants by Status';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    private const COLORS = [
        'active' => '#10b981',    // emerald-500
        'trial' => '#0ea5e9',     // sky-500
        'suspended' => '#f59e0b', // amber-500
        'cancelled' => '#f43f5e', // rose-500
    ];

    /** @return array<string, mixed> */
    public function getData(): array
    {
        $counts = Tenant::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $labels = [];
        $data = [];
        $colors = [];

        foreach (TenantStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);

            if ($count === 0) {
                continue;
            }

            $labels[] = $status->label();
            $data[] = $count;
            $colors[] = self::COLORS[$status->value] ?? '#9ca3af';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tenants',
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
