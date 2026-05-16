<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Firm\Models\Firm;
use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Filament\Admin\Resources\FirmResource\Pages;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * SuperAdmin browsing of accounting firms (Model B top-level entity).
 * One firm owns N tenants: 1 firm_books + 0..N client_books.
 */
class FirmResource extends Resource
{
    protected static ?string $model = Firm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Tenancy';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                    Forms\Components\TextInput::make('tax_id')->maxLength(20),
                    Forms\Components\TextInput::make('commercial_register')->maxLength(30),
                ]),

            Section::make('Address')
                ->columns(2)
                ->collapsible()
                ->schema([
                    Forms\Components\Textarea::make('address')->rows(2),
                    Forms\Components\TextInput::make('city')->maxLength(100),
                ]),

            Section::make('Subscription')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('subscription_tier')
                        ->options(collect(FirmSubscriptionTier::cases())
                            ->mapWithKeys(fn (FirmSubscriptionTier $t) => [$t->value => $t->label()])
                            ->all()),
                    Forms\Components\DateTimePicker::make('subscription_ends_at'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active'    => 'Active',
                            'suspended' => 'Suspended',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('active'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Firm')->searchable()->sortable(),
                TextColumn::make('tax_id')->label('Tax ID')->searchable()->toggleable(),
                TextColumn::make('subscription_tier')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn ($s): string => $s instanceof FirmSubscriptionTier ? $s->label() : (string) ($s ?? '—'))
                    ->color(fn ($s): string => match ($s instanceof FirmSubscriptionTier ? $s->value : $s) {
                        'firm_starter'    => 'gray',
                        'firm_pro'        => 'info',
                        'firm_enterprise' => 'primary',
                        default            => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($s): string => match ($s) {
                        'active'    => 'success',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->counts('tenants')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Staff')
                    ->counts('users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subscription_ends_at')
                    ->label('Renews')
                    ->dateTime('Y-m-d')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('subscription_tier')
                    ->options(collect(FirmSubscriptionTier::cases())
                        ->mapWithKeys(fn (FirmSubscriptionTier $t) => [$t->value => $t->label()])
                        ->all()),
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index'  => Pages\ListFirms::route('/'),
            'create' => Pages\CreateFirm::route('/create'),
            'edit'   => Pages\EditFirm::route('/{record}/edit'),
            'view'   => Pages\ViewFirm::route('/{record}'),
        ];
    }
}
