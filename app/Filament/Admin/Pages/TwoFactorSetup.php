<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Domain\Auth\Services\TwoFactorService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Session;

/**
 * SuperAdmin 2FA enrollment page.
 *
 * Flow:
 *  1. First GET — mint a secret, cache it in the session (not DB) and render
 *     a QR code + manual entry key.
 *  2. User enters a TOTP code from their authenticator app.
 *  3. On verify success, persist the secret + flip `two_factor_enabled`, then
 *     show recovery codes once. From then on `EnforceSuperAdmin2fa` lets them
 *     into the rest of the panel.
 *
 * Slug is `2fa/setup` so the URL matches what `EnforceSuperAdmin2fa`
 * redirects to and what the `ALLOWED_FRAGMENTS` check lets through.
 */
class TwoFactorSetup extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = '2fa/setup';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.pages.two-factor-setup';

    public ?string $code = null;

    public ?string $secret = null;

    /** @var array<int, string>|null */
    public ?array $recoveryCodes = null;

    public function getTitle(): string|Htmlable
    {
        return 'Two-Factor Authentication';
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->two_factor_enabled) {
            // Already enrolled — bounce to dashboard.
            redirect('/admin');

            return;
        }

        // Re-use the in-flight secret so a reload doesn't invalidate enrollment.
        $this->secret = Session::get('2fa.setup.secret') ?? TwoFactorService::generateSecret();
        Session::put('2fa.setup.secret', $this->secret);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('6-digit code from your authenticator app')
                ->required()
                ->length(6)
                ->rule('digits:6')
                ->autocomplete('one-time-code'),
        ]);
    }

    public function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verify and enable')
            ->color('primary')
            ->action(function (): void {
                $this->validate(['code' => 'required|digits:6']);

                $secret = Session::get('2fa.setup.secret');

                if (! $secret || ! $this->matchesSecret($secret, (string) $this->code)) {
                    Notification::make()
                        ->title('Invalid code')
                        ->body('Check that your device clock is accurate and try again.')
                        ->danger()
                        ->send();

                    return;
                }

                $user = auth()->user();
                $recoveryCodes = TwoFactorService::generateRecoveryCodes();

                $user->forceFill([
                    'two_factor_secret' => encrypt($secret),
                    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                    'two_factor_enabled' => true,
                ])->saveQuietly();

                Session::forget('2fa.setup.secret');
                $this->recoveryCodes = $recoveryCodes;

                Notification::make()
                    ->title('Two-factor authentication enabled')
                    ->body('Store the recovery codes below — they will not be shown again.')
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public function continueAction(): Action
    {
        return Action::make('continue')
            ->label('Continue to dashboard')
            ->color('success')
            ->url('/admin')
            ->visible(fn (): bool => $this->recoveryCodes !== null);
    }

    private function matchesSecret(string $secret, string $code): bool
    {
        $time = (int) floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $expected = $this->totpCode($secret, $time + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    private function totpCode(string $secret, int $counter): string
    {
        $binary = $this->base32Decode($secret);
        $time = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $time, $binary, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split(strtoupper($data)) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos !== false) {
                $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        $result = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }

        return $result;
    }
}
