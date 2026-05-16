<x-filament::section
    :heading="__('Usage')"
    :description="__('Current consumption vs plan limits for this tenant.')"
>
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Metric</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Usage</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($rows as $row)
                    @php
                        $limit = $row['limit'];
                        $used = $row['used'];
                        $pct = $limit !== null && $limit > 0 ? min(100, (int) round(($used / $limit) * 100)) : null;
                        $barColor = match (true) {
                            $pct === null => 'bg-gray-400',
                            $pct >= 90 => 'bg-rose-500',
                            $pct >= 70 => 'bg-amber-500',
                            default => 'bg-emerald-500',
                        };
                    @endphp
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $row['label'] }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $row['display'] }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($pct === null)
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">unlimited</span>
                            @else
                                <div class="h-2 w-40 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                    <div class="h-full {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>
