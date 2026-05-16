<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Domain\Shared\Models\FailedJob;
use App\Filament\Admin\Widgets\QueueStatsOverview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use UnitEnum;

/** SuperAdmin page for inspecting failed jobs and queue health. */
class QueueMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'queue-monitor';

    protected static ?string $title = 'Queue Monitor';

    protected string $view = 'filament.admin.pages.queue-monitor';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? 'Queue Monitor';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(FailedJob::query())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('connection')
                    ->label('Connection')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('queue')
                    ->label('Queue')
                    ->badge()
                    ->color('info'),
                TextColumn::make('failed_at')
                    ->label('Failed At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('exception')
                    ->label('Exception')
                    ->formatStateUsing(fn ($state): string => Str::limit((string) $state, 100))
                    ->wrap(),
            ])
            ->defaultSort('failed_at', 'desc')
            ->recordActions([
                Action::make('retry')
                    ->label('Retry')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->action(function (mixed $record): void {
                        Artisan::call('queue:retry', ['id' => [$record->uuid]]);

                        Notification::make()
                            ->title('Job queued for retry')
                            ->body("UUID {$record->uuid}")
                            ->success()
                            ->send();
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (mixed $record): void {
                        Artisan::call('queue:forget', ['id' => $record->uuid]);

                        Notification::make()
                            ->title('Failed job deleted')
                            ->body("UUID {$record->uuid}")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /** @return array<string, Action> */
    protected function getHeaderActions(): array
    {
        return [
            'retry_all' => Action::make('retry_all')
                ->label('Retry all')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Retry all failed jobs?')
                ->modalDescription('Every row in failed_jobs will be queued again.')
                ->action(function (): void {
                    Artisan::call('queue:retry', ['id' => ['all']]);

                    Notification::make()
                        ->title('All failed jobs queued for retry')
                        ->success()
                        ->send();
                }),
            'flush_all' => Action::make('flush_all')
                ->label('Flush all')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Flush all failed jobs?')
                ->modalDescription('This will permanently delete all failed jobs. This action cannot be undone.')
                ->action(function (): void {
                    Artisan::call('queue:flush');

                    Notification::make()
                        ->title('Failed jobs flushed')
                        ->success()
                        ->send();
                }),
        ];
    }

    /** @return array<int, class-string> */
    protected function getHeaderWidgets(): array
    {
        return [
            QueueStatsOverview::class,
        ];
    }
}
