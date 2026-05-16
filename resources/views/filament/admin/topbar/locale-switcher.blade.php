@php
    $current = app()->getLocale();
    $next = $current === 'ar' ? 'EN' : 'AR';
@endphp

<form method="POST" action="{{ route('admin.locale.toggle') }}" class="fi-topbar-item">
    @csrf
    <button
        type="submit"
        title="{{ __('admin.topbar.switch_language') }}"
        class="fi-icon-btn relative flex h-9 w-9 items-center justify-center rounded-lg text-sm font-semibold text-gray-500 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-700 focus-visible:bg-gray-100 focus-visible:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300 dark:focus-visible:bg-white/5 dark:focus-visible:text-gray-300"
    >
        {{ $next }}
    </button>
</form>
