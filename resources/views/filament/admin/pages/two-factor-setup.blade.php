<x-filament-panels::page>
    <div class="mx-auto w-full max-w-xl">
        @if ($recoveryCodes)

            {{-- ─── Success state: recovery codes ─── --}}
            <div class="space-y-6">
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                        <x-filament::icon icon="heroicon-o-check-badge" class="h-8 w-8 text-success-600 dark:text-success-400" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-gray-950 dark:text-white">
                        Two-factor authentication enabled
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Store these recovery codes somewhere safe. Each can be used once if you lose your device — they will not be shown again.
                    </p>
                </div>

                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10">
                    <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                        @foreach ($recoveryCodes as $rc)
                            <div class="rounded bg-white px-3 py-2 text-center tracking-wider text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100">
                                {{ $rc }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-center">
                    {{ $this->continueAction }}
                </div>
            </div>

        @else

            {{-- ─── Setup state: scan + verify ─── --}}
            <div class="space-y-6">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/20">
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h2 class="mt-3 text-lg font-semibold text-gray-950 dark:text-white">
                        Set up two-factor authentication
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Required for SuperAdmin access. Open Google Authenticator, 1Password, Authy, or any TOTP app and add a new account with the key below.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    <div class="space-y-2">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Manual entry key
                        </div>
                        <div class="break-all rounded-lg bg-gray-50 px-3 py-3 font-mono text-sm tracking-wider text-gray-900 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10">
                            {{ $secret }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Account label: <span class="font-mono">{{ auth()->user()?->email ?? 'admin' }}</span> · Type: Time-based (TOTP) · 6 digits
                        </p>
                    </div>
                </div>

                <form wire:submit="verifyAction" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    <div>
                        <div class="text-sm font-medium text-gray-950 dark:text-white">Enter the 6-digit code</div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">From your authenticator app, after scanning above.</p>
                    </div>

                    {{ $this->form }}

                    <div class="flex justify-end">
                        {{ $this->verifyAction }}
                    </div>
                </form>
            </div>

        @endif
    </div>
</x-filament-panels::page>
