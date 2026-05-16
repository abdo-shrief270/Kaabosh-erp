<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Domain\Admin\Services\ImpersonationService;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Tenant\Models\Tenant;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.user.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Select::make('locale')
                        ->label('Locale')
                        ->options(['ar' => 'العربية', 'en' => 'English'])
                        ->default('ar')
                        ->native(false),
                ]),

            Section::make('Access')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('role')
                        ->label('Role')
                        ->options(collect(UserRole::cases())->mapWithKeys(
                            fn (UserRole $r) => [$r->value => $r->label()]
                        )->all())
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty for SuperAdmins.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\Toggle::make('two_factor_enabled')
                        ->label('2FA Enabled')
                        ->disabled()
                        ->helperText('Managed by the user from their profile.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope('tenant'))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof UserRole ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match ($state instanceof UserRole ? $state->value : $state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'accountant' => 'info',
                        'auditor' => 'gray',
                        'client' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->default('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->since()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $r) => [$r->value => $r->label()]
                    )->all()),
                TernaryFilter::make('is_active')->label('Active'),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('danger')
                    ->visible(fn (User $record): bool => auth()->user()?->isSuperAdmin() === true
                        && ! $record->isSuperAdmin()
                        && $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate user')
                    ->modalDescription('You are about to generate a 1-hour API token as this user. This is logged and auditable.')
                    ->modalSubmitActionLabel('Generate token')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->placeholder('Why do you need to impersonate this user? This is logged.')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data, $livewire): void {
                        try {
                            $token = app(ImpersonationService::class)
                                ->impersonateUser($record, (string) $data['reason']);
                        } catch (AuthorizationException $e) {
                            Notification::make()
                                ->title('Impersonation denied')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
                        $impersonateUrl = $frontendUrl.'/auth/impersonate?token='.urlencode($token).'&redirect=%2Fdashboard';

                        Notification::make()
                            ->title('Opening '.$record->email.' in a new tab')
                            ->body(view('filament.admin.notifications.impersonation-token', [
                                'token' => $token,
                                'user' => $record,
                                'url' => $impersonateUrl,
                            ])->render())
                            ->success()
                            ->persistent()
                            ->send();

                        $livewire->js(sprintf('window.open(%s, "_blank", "noopener")', json_encode($impersonateUrl, JSON_UNESCAPED_SLASHES)));
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
