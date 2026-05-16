<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use App\Filament\Admin\Resources\ExchangeRateResource\Pages;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.exchange_rate.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.exchange_rate.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rate')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('base_currency')
                        ->options(fn (): array => Currency::query()->pluck('code', 'code')->all())
                        ->required()
                        ->searchable(),
                    Forms\Components\Select::make('target_currency')
                        ->options(fn (): array => Currency::query()->pluck('code', 'code')->all())
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('rate')
                        ->numeric()
                        ->step('0.000001')
                        ->minValue(0)
                        ->required(),
                    Forms\Components\DatePicker::make('effective_date')->required()->native(false),
                    Forms\Components\TextInput::make('source')
                        ->helperText('e.g. "manual", "ecb", "cbe" (Central Bank of Egypt).')
                        ->maxLength(50)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('base_currency')->label('From')->badge()->color('primary'),
                TextColumn::make('target_currency')->label('To')->badge()->color('primary'),
                TextColumn::make('rate')->sortable()->numeric(decimalPlaces: 6),
                TextColumn::make('effective_date')->date('Y-m-d')->sortable(),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('created_at')->label('Added')->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('base_currency')
                    ->options(fn (): array => Currency::query()->pluck('code', 'code')->all()),
                SelectFilter::make('target_currency')
                    ->options(fn (): array => Currency::query()->pluck('code', 'code')->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('effective_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit' => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
