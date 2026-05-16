<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Models\Plan;
use App\Filament\Admin\Resources\PlanResource\Pages;
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

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'name_en';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.plan.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.plan.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Names')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name_en')
                        ->label('Name (EN)')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name_ar')
                        ->label('Name (AR)')
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['dir' => 'rtl']),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Textarea::make('description_en')
                        ->label('Description (EN)')
                        ->rows(2),
                    Forms\Components\Textarea::make('description_ar')
                        ->label('Description (AR)')
                        ->rows(2)
                        ->extraInputAttributes(['dir' => 'rtl']),
                ]),

            Section::make('Pricing')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('price_monthly')
                        ->label('Price / Month')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('EGP')
                        ->required(),
                    Forms\Components\TextInput::make('price_annual')
                        ->label('Price / Year')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('EGP')
                        ->required(),
                    Forms\Components\Select::make('currency')
                        ->label('Currency')
                        ->options([
                            'EGP' => 'EGP - Egyptian Pound',
                            'USD' => 'USD - US Dollar',
                            'EUR' => 'EUR - Euro',
                        ])
                        ->default('EGP')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('trial_days')
                        ->label('Trial Days')
                        ->numeric()
                        ->default(14)
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),

            Section::make('Limits')
                ->description('Numeric/scalar limits applied to this plan (e.g. users, invoices_per_month).')
                ->schema([
                    Forms\Components\KeyValue::make('limits')
                        ->label('Limits')
                        ->keyLabel('Limit')
                        ->valueLabel('Value')
                        ->addActionLabel('Add limit')
                        ->reorderable(),
                ]),

            Section::make('Features')
                ->description('Toggle the modules & add-ons included in this plan. Stored in the features JSON column.')
                ->schema([
                    Forms\Components\CheckboxList::make('features')
                        ->label('Features')
                        ->options(self::featureOptions())
                        ->descriptions(self::featureDescriptions())
                        ->columns(3)
                        ->bulkToggleable()
                        ->searchable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name_en')
                    ->label('Name')
                    ->description(fn (Plan $record): ?string => $record->name_ar)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price_monthly')
                    ->label('Monthly')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('price_annual')
                    ->label('Annual')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('trial_days')
                    ->label('Trial (d)')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->counts('subscriptions')
                    ->badge(),
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
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    /**
     * Hydrate the DB `features` JSON (associative `{clients: true}` shape) into
     * the flat list of catalog keys that Filament's CheckboxList expects.
     * Unknown keys (e.g. removed from catalog) are dropped so validation passes.
     *
     * @param  array<string, mixed>|list<string>|null  $stored
     * @return list<string>
     */
    public static function featuresToCheckboxState(?array $stored): array
    {
        if (! is_array($stored)) {
            return [];
        }

        $catalog = array_keys(config('features.catalog', []));

        // Support both shapes: ['clients' => true, ...] and ['clients', 'documents'].
        $selected = [];
        foreach ($stored as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $selected[] = $value;
                }
            } elseif ((bool) $value) {
                $selected[] = (string) $key;
            }
        }

        return array_values(array_intersect($selected, $catalog));
    }

    /**
     * Convert the CheckboxList state (flat list) back into the associative
     * `{key: bool}` shape stored in the `features` JSON column.
     *
     * @param  list<string>|null  $selected
     * @return array<string, bool>
     */
    public static function checkboxStateToFeatures(?array $selected): array
    {
        $selected = is_array($selected) ? $selected : [];
        $out = [];
        foreach (array_keys(config('features.catalog', [])) as $key) {
            $out[$key] = in_array($key, $selected, true);
        }

        return $out;
    }

    /** @return array<string, string> */
    protected static function featureOptions(): array
    {
        $catalog = config('features.catalog', []);
        $options = [];
        foreach ($catalog as $key => $meta) {
            $options[$key] = ($meta['name_en'] ?? $key).' · '.($meta['name_ar'] ?? '');
        }

        return $options;
    }

    /** @return array<string, string> */
    protected static function featureDescriptions(): array
    {
        $catalog = config('features.catalog', []);
        $descriptions = [];
        foreach ($catalog as $key => $meta) {
            $category = $meta['category'] ?? 'module';
            $group = $meta['group'] ?? '';
            $descriptions[$key] = ucfirst($category).($group ? " · {$group}" : '');
        }

        return $descriptions;
    }
}
