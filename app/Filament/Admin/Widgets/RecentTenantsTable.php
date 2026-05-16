<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTenantsTable extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Tenants')
            ->description('Newest Active + Trial tenants. Toggle the "Include inactive" filter to see Cancelled/Suspended.')
            ->query(Tenant::query()
                ->whereIn('status', [TenantStatus::Active, TenantStatus::Trial])
                ->latest('created_at')
                ->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TenantStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof TenantStatus ? $state->value : $state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ]);
    }
}
