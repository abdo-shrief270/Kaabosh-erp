<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Webhook\Models\WebhookEndpoint;
use App\Filament\Admin\Resources\WebhookEndpointResource\Pages;
use App\Filament\Admin\Resources\WebhookEndpointResource\RelationManagers\DeliveriesRelationManager;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $recordTitleAttribute = 'url';

    protected static ?int $navigationSort = 80;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.webhook_endpoint.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.webhook_endpoint.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('tenant');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Endpoint')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('secret')
                        ->helperText('Shared secret used for HMAC signing.')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TagsInput::make('events')
                        ->helperText('Event names this endpoint subscribes to (e.g. invoice.created).')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('url')->label('URL')->searchable()->limit(60)->copyable(),
                TextColumn::make('events')
                    ->label('Events')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('failure_count')->label('Failures')->badge()->color(fn ($state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('last_triggered_at')->label('Last triggered')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
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
            ->defaultSort('id', 'desc');
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            DeliveriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'view' => Pages\ViewWebhookEndpoint::route('/{record}'),
            'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit'),
        ];
    }
}
