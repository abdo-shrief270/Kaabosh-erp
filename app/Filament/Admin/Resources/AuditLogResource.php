<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.audit_log.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.audit_log.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Activity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('log_name')->disabled(),
                    Forms\Components\TextInput::make('event')->disabled(),
                    Forms\Components\TextInput::make('description')->disabled()->columnSpanFull(),
                    Forms\Components\TextInput::make('subject_type')->disabled(),
                    Forms\Components\TextInput::make('subject_id')->disabled(),
                    Forms\Components\TextInput::make('causer_type')->disabled(),
                    Forms\Components\TextInput::make('causer_id')->disabled(),
                    Forms\Components\KeyValue::make('properties')->disabled()->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('created_at')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->description(fn ($record): ?string => $record->subject_id ? "#{$record->subject_id}" : null)
                    ->toggleable(),
                TextColumn::make('causer.email')
                    ->label('Causer')
                    ->default('—')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->options(fn (): array => Activity::query()
                        ->select('log_name')
                        ->distinct()
                        ->whereNotNull('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(fn (): array => Activity::query()
                        ->select('subject_type')
                        ->distinct()
                        ->whereNotNull('subject_type')
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($v, $k) => [$k => class_basename((string) $k)])
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
