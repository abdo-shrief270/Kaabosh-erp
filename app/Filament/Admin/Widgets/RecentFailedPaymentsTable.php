<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Subscription\Models\SubscriptionPayment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class RecentFailedPaymentsTable extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Failed Payments (30d)')
            ->query(
                SubscriptionPayment::query()
                    ->withoutGlobalScope('tenant')
                    ->whereNotNull('failed_at')
                    ->where('failed_at', '>=', now()->subDays(30))
                    ->with('tenant')
                    ->latest('failed_at')
                    ->limit(10),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EGP'),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum ? $state->value : (string) $state),
                TextColumn::make('failure_reason')
                    ->label('Reason')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : Str::limit($state, 60))
                    ->wrap(),
                TextColumn::make('failed_at')
                    ->label('Failed')
                    ->since(),
            ]);
    }
}
