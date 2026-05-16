<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Domain\Subscription\Models\AddOn;
use App\Filament\Admin\Resources\AddOnResource\Pages;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Catalog management for purchasable add-ons.
 *
 * The form layout is type-conditional: a `boost` add-on shows the limits
 * KeyValue editor, a `feature` add-on shows the feature-slug picker, and a
 * `credit_pack` add-on shows the credit kind + quantity inputs. Hiding the
 * unused fields keeps the SuperAdmin workflow honest — accidentally setting
 * a `feature_slug` on a credit pack would silently do nothing at runtime.
 */
class AddOnResource extends Resource
{
    protected static ?string $model = AddOn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'name_en';

    protected static ?int $navigationSort = 25;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.add_on.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.add_on.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText('Stable identifier — used in URLs and idempotency keys. Lowercase, snake_case.'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('name_en')
                        ->label('Name (EN)')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('name_ar')
                        ->label('Name (AR)')
                        ->required()
                        ->maxLength(100)
                        ->extraInputAttributes(['dir' => 'rtl']),
                    Forms\Components\Textarea::make('description_en')
                        ->label('Description (EN)')
                        ->rows(2),
                    Forms\Components\Textarea::make('description_ar')
                        ->label('Description (AR)')
                        ->rows(2)
                        ->extraInputAttributes(['dir' => 'rtl']),
                ]),

            Section::make('Type & cycle')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(self::typeOptions())
                        ->required()
                        ->live()
                        ->helperText('Boost raises a plan limit; feature unlocks a flag; credit pack grants N consumable credits.'),
                    Forms\Components\Select::make('billing_cycle')
                        ->options(self::cycleOptions())
                        ->required()
                        ->default(AddOnBillingCycle::Monthly->value)
                        ->disabled(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value)
                        ->dehydrated()
                        ->helperText('Credit packs are one-time and cycle is forced to "once".'),
                ]),

            // Boost-specific: limit deltas
            Section::make('Boost contribution')
                ->visible(fn (Get $get): bool => $get('type') === AddOnType::Boost->value)
                ->schema([
                    Forms\Components\KeyValue::make('boost')
                        ->keyLabel('Limit key (e.g. max_users)')
                        ->valueLabel('Delta')
                        ->reorderable(false)
                        ->addActionLabel('Add limit boost')
                        ->helperText('Keys must match Plan.limits keys exactly. Values are added to the plan limit; -1 stays unlimited.'),
                ]),

            // Feature-specific
            Section::make('Feature unlock')
                ->visible(fn (Get $get): bool => $get('type') === AddOnType::Feature->value)
                ->schema([
                    Forms\Components\Select::make('feature_slug')
                        ->options(self::featureCatalog())
                        ->searchable()
                        ->helperText('Must be a slug from config/features.php catalog.'),
                ]),

            // Credit-pack-specific
            Section::make('Credit pack')
                ->visible(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value)
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('credit_kind')
                        ->options([
                            'sms' => 'SMS messages',
                            'ai_tokens' => 'AI tokens',
                            'whatsapp' => 'WhatsApp messages',
                        ])
                        ->required(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value),
                    Forms\Components\TextInput::make('credit_quantity')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99999999)
                        ->required(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value)
                        ->helperText('Credits granted per purchase. Multiplied by quantity at checkout.'),
                ]),

            Section::make('Pricing')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('price_monthly')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('EGP')
                        ->default(0)
                        ->disabled(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value)
                        ->dehydrated(),
                    Forms\Components\TextInput::make('price_annual')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('EGP')
                        ->default(0)
                        ->disabled(fn (Get $get): bool => $get('type') === AddOnType::CreditPack->value)
                        ->dehydrated(),
                    Forms\Components\TextInput::make('price_once')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('EGP')
                        ->default(0)
                        ->disabled(fn (Get $get): bool => $get('type') !== AddOnType::CreditPack->value)
                        ->dehydrated(),
                    Forms\Components\Select::make('currency')
                        ->options([
                            'EGP' => 'EGP - Egyptian Pound',
                            'USD' => 'USD - US Dollar',
                            'EUR' => 'EUR - Euro',
                        ])
                        ->default('EGP')
                        ->required(),
                ]),

            Section::make('Visibility')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->helperText('Inactive add-ons are hidden from the catalog but existing subscriptions keep working.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('sm'),
                TextColumn::make('name_en')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AddOnType ? $state->label() : (string) $state)
                    ->colors([
                        'success' => 'boost',
                        'primary' => 'feature',
                        'warning' => 'credit_pack',
                    ]),
                TextColumn::make('billing_cycle')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => $state instanceof AddOnBillingCycle ? $state->label() : (string) $state),
                TextColumn::make('price_monthly')
                    ->label('Monthly')
                    ->money('EGP')
                    ->visible(fn ($livewire): bool => true),
                TextColumn::make('price_once')
                    ->label('Once')
                    ->money('EGP')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('subscription_add_ons_count')
                    ->label('Active')
                    ->counts(['subscriptionAddOns' => fn ($q) => $q->where('status', 'active')])
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::typeOptions()),
                SelectFilter::make('billing_cycle')->options(self::cycleOptions()),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddOns::route('/'),
            'create' => Pages\CreateAddOn::route('/create'),
            'edit' => Pages\EditAddOn::route('/{record}/edit'),
        ];
    }

    /**
     * Normalize the form payload before insert/update so each add-on type
     * stores only the fields it uses. Stops a stale `feature_slug` left over
     * from a type switch from leaking into a credit-pack row.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormPayload(array $data): array
    {
        $type = $data['type'] ?? null;

        if ($type === AddOnType::CreditPack->value) {
            $data['billing_cycle'] = AddOnBillingCycle::Once->value;
            $data['price_monthly'] = '0';
            $data['price_annual'] = '0';
            $data['boost'] = null;
            $data['feature_slug'] = null;
        }

        if ($type === AddOnType::Boost->value) {
            $data['feature_slug'] = null;
            $data['credit_kind'] = null;
            $data['credit_quantity'] = null;
            $data['price_once'] = '0';
        }

        if ($type === AddOnType::Feature->value) {
            $data['boost'] = null;
            $data['credit_kind'] = null;
            $data['credit_quantity'] = null;
            $data['price_once'] = '0';
        }

        // Boost JSON arrives as { key: stringValue }; coerce values to ints
        // so the runtime merge in AddOnService doesn't have to.
        if (is_array($data['boost'] ?? null)) {
            $data['boost'] = array_map(static fn ($v): int => (int) $v, $data['boost']);
        }

        return $data;
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        $opts = [];
        foreach (AddOnType::cases() as $case) {
            $opts[$case->value] = $case->label();
        }

        return $opts;
    }

    /** @return array<string, string> */
    private static function cycleOptions(): array
    {
        $opts = [];
        foreach (AddOnBillingCycle::cases() as $case) {
            $opts[$case->value] = $case->label();
        }

        return $opts;
    }

    /** @return array<string, string> */
    private static function featureCatalog(): array
    {
        $catalog = config('features.catalog', []);
        $opts = [];
        foreach ($catalog as $key => $meta) {
            $name = $meta['name_en'] ?? ucfirst(str_replace('_', ' ', (string) $key));
            $opts[$key] = "{$name} ({$key})";
        }
        ksort($opts);

        return $opts;
    }
}
