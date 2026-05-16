<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Enums\DiscountType;
use App\Domain\Subscription\Models\Coupon;
use App\Domain\Subscription\Models\Plan;
use App\Filament\Admin\Resources\CouponResource\Pages;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 45;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.coupon.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.coupon.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true)
                        ->helperText('Case-sensitive code customers enter at checkout.'),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('discount_type')
                        ->required()
                        ->options(collect(DiscountType::cases())->mapWithKeys(
                            fn (DiscountType $t) => [$t->value => $t->label()]
                        )->all()),
                    Forms\Components\TextInput::make('discount_value')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->helperText('For "Percent off" this is a percentage (0–100); for "Fixed amount off" it is in the currency below.'),
                    Forms\Components\TextInput::make('currency')
                        ->maxLength(3)
                        ->default('EGP'),
                    Forms\Components\TextInput::make('max_uses')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leave blank for unlimited redemptions.'),
                    Forms\Components\Select::make('applies_to_plan_ids')
                        ->label('Restricted to plans')
                        ->multiple()
                        ->options(fn (): array => Plan::query()->pluck('name_en', 'id')->all())
                        ->helperText('Leave empty to allow the coupon on every plan.')
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->helperText('Leave blank for no expiry.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DiscountType ? $state->label() : (string) $state)
                    ->color('info'),
                TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, Coupon $record): string => $record->discount_type === DiscountType::Percent
                        ? rtrim(rtrim((string) $state, '0'), '.').'%'
                        : number_format((float) $state, 2).' '.($record->currency ?: 'EGP'))
                    ->sortable(),
                TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn ($state, Coupon $record): string => $record->max_uses
                        ? "{$state} / {$record->max_uses}"
                        : (string) $state)
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                SelectFilter::make('discount_type')
                    ->options(collect(DiscountType::cases())->mapWithKeys(
                        fn (DiscountType $t) => [$t->value => $t->label()]
                    )->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
