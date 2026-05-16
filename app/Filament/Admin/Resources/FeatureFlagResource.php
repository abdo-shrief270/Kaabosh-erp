<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Tenant\Models\Tenant;
use App\Filament\Admin\Resources\FeatureFlagResource\Pages;
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

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.feature_flag.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.feature_flag.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Feature')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('key')
                        ->label('Feature Key')
                        ->options(self::catalogOptions())
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')
                        ->label('Display Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('Scope')
                ->description('Global toggle + per-plan / per-tenant overrides. Tenant disables override all other signals.')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_enabled_globally')
                        ->label('Enabled Globally')
                        ->default(false),
                    Forms\Components\TextInput::make('rollout_percentage')
                        ->label('Rollout % (0-100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Deterministic gradual rollout based on tenant ID hash.'),
                    Forms\Components\Select::make('enabled_for_plans')
                        ->label('Enabled for Plans')
                        ->multiple()
                        ->options(fn (): array => Plan::query()->pluck('name_en', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('enabled_for_tenants')
                        ->label('Enabled for Tenants')
                        ->multiple()
                        ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('disabled_for_tenants')
                        ->label('Disabled for Tenants')
                        ->multiple()
                        ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                IconColumn::make('is_enabled_globally')
                    ->label('Global')
                    ->boolean(),
                TextColumn::make('rollout_percentage')
                    ->label('Rollout')
                    ->formatStateUsing(fn ($state): string => $state ? "{$state}%" : '—'),
                TextColumn::make('enabled_for_plans')
                    ->label('Plans')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->badge(),
                TextColumn::make('enabled_for_tenants')
                    ->label('Tenants (on)')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->badge()
                    ->color('success'),
                TextColumn::make('disabled_for_tenants')
                    ->label('Tenants (off)')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled_globally')->label('Enabled globally'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('key', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }

    /** @return array<string, string> */
    protected static function catalogOptions(): array
    {
        $catalog = config('features.catalog', []);
        $options = [];
        foreach ($catalog as $key => $meta) {
            $options[$key] = ($meta['name_en'] ?? $key)." ({$key})";
        }

        return $options;
    }
}
