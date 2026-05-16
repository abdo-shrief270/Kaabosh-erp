@props(['token' => '', 'user' => null, 'url' => null])

<div class="space-y-2" x-data="{ copied: false }">
    <p class="text-sm">
        A 1-hour API token was generated for
        <strong>{{ $user?->email }}</strong>.
        A new tab should open automatically at the firm's dashboard.
    </p>

    @if ($url)
        <p class="text-sm">
            If your browser blocked the popup,
            <a href="{{ $url }}"
               target="_blank"
               rel="noopener"
               class="underline text-primary-600">open the dashboard manually</a>.
        </p>
    @endif

    <details class="text-xs">
        <summary class="cursor-pointer select-none">Show raw token</summary>
        <pre class="font-mono text-xs bg-gray-900 text-gray-100 rounded p-2 mt-1 whitespace-pre-wrap break-all"
             x-ref="tok">{{ $token }}</pre>
        <button type="button"
                class="underline mt-1"
                x-on:click="navigator.clipboard.writeText($refs.tok.innerText); copied = true; setTimeout(() => copied = false, 2000)">
            <span x-show="!copied">Copy to clipboard</span>
            <span x-show="copied" x-cloak>Copied!</span>
        </button>
    </details>
</div>
