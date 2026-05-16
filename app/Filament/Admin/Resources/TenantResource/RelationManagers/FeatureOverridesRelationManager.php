<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TenantResource\RelationManagers;

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Tenant\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/** Pseudo-relation manager listing all feature flags with per-tenant override state. */
class FeatureOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'featureOverrides';

    protected static ?string $title = 'Feature Overrides';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    /** Bypass the normal relationship plumbing — return an empty builder so Filament doesn't complain. */
    public function getRelationship(): Relation|Builder
    {
        return FeatureFlag::query();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => FeatureFlag::query())
            ->recordTitleAttribute('key')
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
                TextColumn::make('override_state')
                    ->label('Override')
                    ->badge()
                    ->state(fn (FeatureFlag $record): string => $this->resolveOverrideState($record))
                    ->color(fn (string $state): string => match ($state) {
                        'forced_on' => 'success',
                        'forced_off' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('setOverride')
                    ->label('Set Override')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->schema([
                        Select::make('state')
                            ->label('Override State')
                            ->options([
                                'forced_on' => 'Forced On (enabled for this tenant)',
                                'forced_off' => 'Forced Off (disabled for this tenant)',
                                'default' => 'Default (no override)',
                            ])
                            ->required()
                            ->native(false)
                            ->default(fn (FeatureFlag $record): string => $this->resolveOverrideState($record)),
                    ])
                    ->action(function (FeatureFlag $record, array $data): void {
                        $this->applyOverride($record, $data['state']);

                        Notification::make()
                            ->title('Override updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /** Determine the current override state for this tenant on the given flag. */
    protected function resolveOverrideState(FeatureFlag $flag): string
    {
        /** @var Tenant $tenant */
        $tenant = $this->getOwnerRecord();
        $tenantId = $tenant->id;

        if (in_array($tenantId, $flag->disabled_for_tenants ?? [], true)) {
            return 'forced_off';
        }

        if (in_array($tenantId, $flag->enabled_for_tenants ?? [], true)) {
            return 'forced_on';
        }

        return 'default';
    }

    /** Apply a new override state by normalising both tenant lists on the flag. */
    protected function applyOverride(FeatureFlag $flag, string $state): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->getOwnerRecord();
        $tenantId = $tenant->id;

        $enabled = array_values(array_filter(
            $flag->enabled_for_tenants ?? [],
            static fn ($id): bool => (int) $id !== $tenantId,
        ));
        $disabled = array_values(array_filter(
            $flag->disabled_for_tenants ?? [],
            static fn ($id): bool => (int) $id !== $tenantId,
        ));

        if ($state === 'forced_on') {
            $enabled[] = $tenantId;
        } elseif ($state === 'forced_off') {
            $disabled[] = $tenantId;
        }

        $flag->enabled_for_tenants = $enabled;
        $flag->disabled_for_tenants = $disabled;
        $flag->save();
    }

    /** Override the viewAny authorization since `featureOverrides` is not a real relation. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Tenant;
    }

    /** No create — flags are managed elsewhere. */
    protected function canCreate(): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    protected function canDeleteAny(): bool
    {
        return false;
    }
}
