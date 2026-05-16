<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Communication\Models\EmailTemplate;
use App\Filament\Admin\Resources\EmailTemplateResource\Pages;
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

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 90;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.email_template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.email_template.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Stable identifier used by code (e.g. "invoice.reminder").')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')->default(true)->columnSpanFull(),
                ]),

            Section::make('English')
                ->columns(1)
                ->schema([
                    Forms\Components\TextInput::make('subject_en')->label('Subject')->required()->maxLength(255),
                    Forms\Components\Textarea::make('body_en')->label('Body')->required()->rows(8),
                ]),

            Section::make('Arabic')
                ->columns(1)
                ->schema([
                    Forms\Components\TextInput::make('subject_ar')
                        ->label('Subject')
                        ->required()
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->maxLength(255),
                    Forms\Components\Textarea::make('body_ar')
                        ->label('Body')
                        ->required()
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->rows(8),
                ]),

            Section::make('Variables')
                ->schema([
                    Forms\Components\TagsInput::make('variables')
                        ->helperText('List of placeholder names available to this template (for documentation).')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('subject_en')->label('Subject (EN)')->limit(40)->toggleable(),
                TextColumn::make('subject_ar')->label('Subject (AR)')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('updated_at')->label('Updated')->since()->toggleable(),
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
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
