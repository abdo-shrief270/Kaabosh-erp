<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Enums\SubscriptionAddOnStatus;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use App\Domain\Subscription\Services\AddOnService;
use App\Filament\Admin\Resources\SubscriptionAddOnResource\Pages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Super-admin list of every `SubscriptionAddOn` row across all tenants.
 *
 * Built primarily to unstick add-ons whose gateway webhook never fired
 * (dev environments without real Paymob/Fawry, transient capture failures,
 * etc.) — the "Mark Paid" action calls `AddOnService::activate()` to flip
 * a pending row to active and seed any credit balance.
 *
 * Read-only otherwise. Add-ons are created via the tenant-side purchase
 * flow; deletion is intentionally not exposed.
 */
class SubscriptionAddOnResource extends Resource
{
    protected static ?string $model = SubscriptionAddOn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return 'Subscription Add-on';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Subscription Add-ons';
    }

    public static function form(Schema $schema): Schema
    {
        // No editing — purchases happen through the tenant flow.
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(SubscriptionAddOn::query()->with(['tenant', 'addOn']))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('addOn.name_en')
                    ->label('Add-on')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof SubscriptionAddOnStatus ? $state->value : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof SubscriptionAddOnStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'failed', 'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->money(fn (SubscriptionAddOn $r): string => $r->currency ?? 'EGP')
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->label('Period End')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Activate this add-on as if the gateway had confirmed payment. Use for stuck rows where the webhook never fired.')
                    ->visible(fn (SubscriptionAddOn $r): bool => $r->status === SubscriptionAddOnStatus::Pending)
                    ->action(function (SubscriptionAddOn $r): void {
                        app(AddOnService::class)->activate($r);

                        Notification::make()
                            ->title('Add-on activated')
                            ->body('Limits and credits (if any) are now in effect.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionAddOns::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
