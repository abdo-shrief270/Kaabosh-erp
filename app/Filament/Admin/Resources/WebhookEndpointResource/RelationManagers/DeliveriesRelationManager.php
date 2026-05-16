<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WebhookEndpointResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    protected static ?string $title = 'Recent Deliveries';

    public function form(Schema $schema): Schema
    {
        // Deliveries are not manually created/edited — all fields are read-only.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                TextColumn::make('event')->badge()->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'delivered', 'success' => 'success',
                        'failed' => 'danger',
                        'pending', 'retrying' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status_code')->label('HTTP')->toggleable(),
                TextColumn::make('attempt')->label('Attempt')->sortable()->toggleable(),
                TextColumn::make('duration_ms')->label('Duration (ms)')->sortable()->toggleable(),
                TextColumn::make('error_message')->label('Error')->limit(60)->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('When')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'retrying' => 'Retrying',
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
