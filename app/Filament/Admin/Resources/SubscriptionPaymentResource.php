<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\PaymentStatus;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Domain\Tenant\Models\Tenant;
use App\Filament\Admin\Resources\SubscriptionPaymentResource\Pages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.subscription_payment.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.subscription_payment.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $failed = static::getEloquentQuery()
            ->where('status', PaymentStatus::Failed)
            ->count();

        return $failed > 0 ? (string) $failed : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('tenant');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('EGP')
                        ->disabled(),
                    Forms\Components\TextInput::make('currency')->disabled(),
                    Forms\Components\TextInput::make('status')->disabled(),
                    Forms\Components\TextInput::make('gateway')->disabled(),
                    Forms\Components\TextInput::make('gateway_transaction_id')->disabled(),
                    Forms\Components\TextInput::make('gateway_order_id')->disabled(),
                    Forms\Components\DatePicker::make('billing_period_start')->disabled(),
                    Forms\Components\DatePicker::make('billing_period_end')->disabled(),
                    Forms\Components\DateTimePicker::make('paid_at')->disabled(),
                    Forms\Components\DateTimePicker::make('failed_at')->disabled(),
                    Forms\Components\DateTimePicker::make('refunded_at')->disabled(),
                    Forms\Components\TextInput::make('receipt_url')->disabled(),
                    Forms\Components\Textarea::make('failure_reason')->disabled()->columnSpanFull()->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscription_id')
                    ->label('Sub #')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentGateway ? $state->label() : (string) $state)
                    ->color('info'),
                TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('failed_at')
                    ->label('Failed')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failure_reason')
                    ->label('Reason')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $s) => [$s->value => $s->label()]
                    )->all()),
                SelectFilter::make('gateway')
                    ->options(collect(PaymentGateway::cases())->mapWithKeys(
                        fn (PaymentGateway $g) => [$g->value => $g->label()]
                    )->all()),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                    ->searchable(),
                Filter::make('created_between')
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('mark_completed')
                    ->label('Mark completed')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->status === PaymentStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription('Manually mark this payment as completed. Use only for reconciled manual-gateway payments.')
                    ->action(function (SubscriptionPayment $record): void {
                        $record->forceFill([
                            'status' => PaymentStatus::Completed,
                            'paid_at' => now(),
                            'failed_at' => null,
                            'failure_reason' => null,
                        ])->save();

                        Notification::make()->title('Payment marked completed')->success()->send();
                    }),
                Action::make('mark_refunded')
                    ->label('Mark refunded')
                    ->icon(Heroicon::OutlinedReceiptRefund)
                    ->color('warning')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->status === PaymentStatus::Completed)
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Refund note (stored in metadata)')
                            ->required()
                            ->minLength(5)
                            ->rows(2),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        $metadata = $record->metadata ?? [];
                        $metadata['refund_note'] = $data['note'];
                        $metadata['refunded_by'] = Auth::id();

                        $record->forceFill([
                            'status' => PaymentStatus::Refunded,
                            'refunded_at' => now(),
                            'metadata' => $metadata,
                        ])->save();

                        Notification::make()->title('Payment marked refunded')->success()->send();
                    }),
                Action::make('retry')
                    ->label('Reset to pending')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('primary')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->status === PaymentStatus::Failed)
                    ->requiresConfirmation()
                    ->modalDescription('Reset this failed payment to pending so the gateway webhook / retry job can reprocess it.')
                    ->action(function (SubscriptionPayment $record): void {
                        $record->forceFill([
                            'status' => PaymentStatus::Pending,
                            'failed_at' => null,
                            'failure_reason' => null,
                        ])->save();

                        Notification::make()->title('Payment reset to pending')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPayments::route('/'),
            'view' => Pages\ViewSubscriptionPayment::route('/{record}'),
        ];
    }
}
