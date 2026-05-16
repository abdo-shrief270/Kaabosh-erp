<div
    x-data="{ open: @entangle('open') }"
    @keydown.escape.window="open = false"
    class="fi-topbar-item relative"
>
    <button
        type="button"
        @click="open = ! open"
        title="{{ __('admin.topbar.notifications') }}"
        class="fi-icon-btn relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-700 focus-visible:bg-gray-100 focus-visible:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300 dark:focus-visible:bg-white/5 dark:focus-visible:text-gray-300"
    >
        <x-filament::icon
            icon="heroicon-o-bell"
            class="h-5 w-5"
        />

        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -end-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-white dark:ring-gray-900">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        @click.outside="open = false"
        class="fi-dropdown-panel absolute end-0 mt-2 w-80 origin-top-end rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 z-40"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2.5 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('admin.topbar.notifications') }}
            </h3>

            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                >
                    {{ __('admin.topbar.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto py-1">
            @forelse ($notifications as $notification)
                @php
                    $title = $locale === 'ar'
                        ? ($notification->title_ar ?: $notification->title_en)
                        : ($notification->title_en ?: $notification->title_ar);
                    $body = $locale === 'ar'
                        ? ($notification->body_ar ?: $notification->body_en)
                        : ($notification->body_en ?: $notification->body_ar);
                @endphp

                <button
                    type="button"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="flex w-full items-start gap-3 px-4 py-3 text-start transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $notification->isRead() ? '' : 'bg-primary-50/50 dark:bg-primary-500/5' }}"
                >
                    @unless ($notification->isRead())
                        <span class="mt-1.5 h-2 w-2 flex-none rounded-full bg-primary-500"></span>
                    @else
                        <span class="mt-1.5 h-2 w-2 flex-none"></span>
                    @endunless

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                            {{ $title }}
                        </p>

                        @if ($body)
                            <p class="mt-0.5 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $body }}
                            </p>
                        @endif

                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at?->diffForHumans() }}
                        </p>
                    </div>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.topbar.no_notifications') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
