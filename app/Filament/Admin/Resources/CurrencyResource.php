<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Currency\Models\Currency;
use App\Filament\Admin\Resources\CurrencyResource\Pages;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.currency.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.currency.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Currency')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('ISO Code')
                        ->required()
                        ->length(3)
                        ->unique(ignoreRecord: true)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\TextInput::make('symbol')->required()->maxLength(10),
                    Forms\Components\TextInput::make('name_en')->label('Name (EN)')->required()->maxLength(255),
                    Forms\Components\TextInput::make('name_ar')
                        ->label('Name (AR)')
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['dir' => 'rtl']),
                    Forms\Components\TextInput::make('decimal_places')->numeric()->default(2)->minValue(0)->maxValue(6),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->color('primary')->sortable()->searchable(),
                TextColumn::make('name_en')->label('Name (EN)')->searchable()->sortable(),
                TextColumn::make('name_ar')->label('Name (AR)')->toggleable(),
                TextColumn::make('symbol')->toggleable(),
                TextColumn::make('decimal_places')->label('Decimals')->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
