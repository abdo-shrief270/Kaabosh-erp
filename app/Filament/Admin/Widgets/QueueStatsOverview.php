<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Ops stats for the queue monitor page. */
class QueueStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Queue Health';

    protected static ?int $sort = 0;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $failedTotal = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->count()
            : 0;

        $driver = (string) config('queue.default', 'sync');
        $pendingLabel = $this->resolvePendingLabel($driver);

        $failedLastWeek = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subWeek())
                ->count()
            : 0;

        return [
            Stat::make('Failed jobs', (string) $failedTotal)
                ->description('Total rows in failed_jobs')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($failedTotal > 0 ? 'danger' : 'success'),

            Stat::make('Pending jobs', $pendingLabel)
                ->description('Awaiting workers on default connection')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color('info'),

            Stat::make('Failed (last 7 days)', (string) $failedLastWeek)
                ->description('Recent failures across all queues')
                ->descriptionIcon('heroicon-m-clock')
                ->color($failedLastWeek > 0 ? 'warning' : 'gray'),
        ];
    }

    /** Return pending job count or a "N/A — using {driver}" placeholder. */
    private function resolvePendingLabel(string $driver): string
    {
        if ($driver !== 'database') {
            return "N/A — using {$driver}";
        }

        $table = (string) config('queue.connections.database.table', 'jobs');

        if (! Schema::hasTable($table)) {
            return '0';
        }

        return (string) DB::table($table)->count();
    }
}
