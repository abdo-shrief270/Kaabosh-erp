<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Enums\DiscountType;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Coupon;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.subscription.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.subscription.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('plan_id')
                        ->label('Plan')
                        ->relationship('plan', 'name_en')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                            fn (SubscriptionStatus $s) => [$s->value => $s->label()]
                        )->all())
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('billing_cycle')
                        ->label('Billing Cycle')
                        ->options([
                            'monthly' => 'Monthly',
                            'annual' => 'Annual',
                        ])
                        ->default('monthly')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->prefix('EGP')
                        ->minValue(0),
                    Forms\Components\TextInput::make('currency')
                        ->label('Currency')
                        ->default('EGP')
                        ->maxLength(3),
                ]),

            Section::make('Dates')
                ->columns(3)
                ->schema([
                    Forms\Components\DateTimePicker::make('trial_ends_at')
                        ->label('Trial Ends At'),
                    Forms\Components\DatePicker::make('current_period_start')
                        ->label('Period Start'),
                    Forms\Components\DatePicker::make('current_period_end')
                        ->label('Period End'),
                    Forms\Components\DateTimePicker::make('cancelled_at')
                        ->label('Cancelled At'),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At'),
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope('tenant'))
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name_en')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof SubscriptionStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof SubscriptionStatus ? $state->value : $state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due' => 'warning',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('current_period_start')
                    ->label('Starts')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->label('Ends')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                        fn (SubscriptionStatus $s) => [$s->value => $s->label()]
                    )->all()),
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn (): array => Plan::query()->pluck('name_en', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('extend_trial')
                    ->label('Extend trial')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('warning')
                    ->visible(fn (Subscription $record): bool => $record->status === SubscriptionStatus::Trial)
                    ->schema([
                        Forms\Components\TextInput::make('days')
                            ->label('Extra days')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required()
                            ->default(7),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->minLength(5)
                            ->rows(2),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        $days = (int) $data['days'];
                        $base = $record->trial_ends_at ?? now();
                        $newEnds = $base->copy()->addDays($days);

                        $metadata = $record->metadata ?? [];
                        $metadata['trial_extensions'][] = [
                            'days' => $days,
                            'reason' => $data['reason'],
                            'by' => Auth::id(),
                            'at' => now()->toIso8601String(),
                        ];

                        $record->forceFill([
                            'trial_ends_at' => $newEnds,
                            'metadata' => $metadata,
                        ])->save();

                        Notification::make()
                            ->title("Trial extended by {$days} days")
                            ->body('New end: '.$newEnds->format('Y-m-d H:i'))
                            ->success()
                            ->send();
                    }),
                Action::make('apply_coupon')
                    ->label('Apply coupon')
                    ->icon(Heroicon::OutlinedTicket)
                    ->color('primary')
                    ->visible(fn (Subscription $record): bool => in_array(
                        $record->status,
                        [SubscriptionStatus::Trial, SubscriptionStatus::Active],
                        true
                    ))
                    ->schema([
                        Forms\Components\Select::make('coupon_id')
                            ->label('Coupon')
                            ->options(fn (): array => Coupon::query()
                                ->redeemable()
                                ->pluck('code', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        $coupon = Coupon::query()->findOrFail($data['coupon_id']);

                        if (! $coupon->appliesToPlan((int) $record->plan_id)) {
                            Notification::make()
                                ->title('Coupon not valid for this plan')
                                ->danger()
                                ->send();

                            return;
                        }

                        $originalPrice = (float) $record->price;
                        $discount = $coupon->discountFor($originalPrice);
                        $newPrice = round(max(0, $originalPrice - $discount), 2);

                        DB::transaction(function () use ($record, $coupon, $originalPrice, $discount, $newPrice): void {
                            $metadata = $record->metadata ?? [];
                            $metadata['coupon_applications'][] = [
                                'coupon_id' => $coupon->id,
                                'coupon_code' => $coupon->code,
                                'discount_type' => $coupon->discount_type->value,
                                'discount_value' => (float) $coupon->discount_value,
                                'original_price' => $originalPrice,
                                'discount' => $discount,
                                'new_price' => $newPrice,
                                'by' => Auth::id(),
                                'at' => now()->toIso8601String(),
                            ];

                            $record->forceFill([
                                'price' => $newPrice,
                                'metadata' => $metadata,
                            ])->save();

                            $coupon->increment('used_count');
                        });

                        $label = $coupon->discount_type === DiscountType::Percent
                            ? number_format((float) $coupon->discount_value, 2).'%'
                            : number_format((float) $coupon->discount_value, 2).' '.$coupon->currency;

                        Notification::make()
                            ->title("Coupon {$coupon->code} applied ({$label})")
                            ->body("Price: {$originalPrice} → {$newPrice}")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
