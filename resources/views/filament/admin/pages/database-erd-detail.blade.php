@php
    /** @var array<string, mixed> $table */
    /** @var list<array<string, mixed>> $incoming */
    $primaryKey = $table['primary_key'] ?? [];
    $fkColumns = collect($table['foreign_keys'])->flatMap(fn ($fk) => $fk['columns'])->all();
    $uniqueColumns = collect($table['indexes'])
        ->filter(fn ($i) => $i['unique'] && ! $i['primary'] && count($i['columns']) === 1)
        ->map(fn ($i) => $i['columns'][0])
        ->all();
@endphp

<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-start justify-between rounded-lg bg-white p-4 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div>
            <h2 class="font-mono text-xl font-semibold">{{ $table['name'] }}</h2>
            @if (! empty($table['comment']))
                <p class="mt-1 text-sm text-gray-500">{{ $table['comment'] }}</p>
            @endif
            <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                <span>{{ count($table['columns']) }} columns</span>
                <span>·</span>
                <span>{{ count($table['foreign_keys']) }} FKs out</span>
                <span>·</span>
                <span>{{ count($incoming) }} FKs in</span>
                <span>·</span>
                <span>{{ count($table['indexes']) }} indexes</span>
            </div>
        </div>
        <button
            type="button"
            wire:click="clearSelection"
            class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
        >Close</button>
    </div>

    {{-- Columns --}}
    <div class="rounded-lg bg-white shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold dark:border-white/10">Columns</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Null</th>
                        <th class="px-3 py-2">Default</th>
                        <th class="px-3 py-2">Flags</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($table['columns'] as $col)
                        @php
                            $isPk = in_array($col['name'], $primaryKey, true);
                            $isFk = in_array($col['name'], $fkColumns, true);
                            $isUnique = in_array($col['name'], $uniqueColumns, true);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 font-mono">{{ $col['name'] }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $col['type'] }}</td>
                            <td class="px-3 py-2 text-xs">
                                @if ($col['nullable'])
                                    <span class="text-gray-500">YES</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400">NO</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-500">
                                {{ $col['default'] !== null ? \Illuminate\Support\Str::limit((string) $col['default'], 40) : '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @if ($isPk)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">PK</span>
                                    @endif
                                    @if ($isFk)
                                        <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-semibold text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">FK</span>
                                    @endif
                                    @if ($isUnique)
                                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">UQ</span>
                                    @endif
                                    @if ($col['auto_increment'])
                                        <span class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">AUTO</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Foreign keys (outgoing) --}}
    @if (! empty($table['foreign_keys']))
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold dark:border-white/10">Foreign keys (outgoing)</div>
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($table['foreign_keys'] as $fk)
                    <li class="flex flex-wrap items-center gap-2 px-4 py-2 text-sm">
                        <span class="font-mono text-xs">{{ implode(',', $fk['columns']) }}</span>
                        <span class="text-gray-400">→</span>
                        <button
                            type="button"
                            wire:click="selectTable('{{ $fk['foreign_table'] }}')"
                            class="font-mono text-primary-600 hover:underline dark:text-primary-400"
                        >{{ $fk['foreign_table'] }}</button>
                        <span class="font-mono text-xs">({{ implode(',', $fk['foreign_columns']) }})</span>
                        @if ($fk['on_delete'])
                            <span class="ml-auto rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-mono text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                ON DELETE {{ strtoupper($fk['on_delete']) }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Reverse FKs --}}
    @if (! empty($incoming))
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold dark:border-white/10">Referenced by (incoming)</div>
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($incoming as $row)
                    <li class="flex flex-wrap items-center gap-2 px-4 py-2 text-sm">
                        <button
                            type="button"
                            wire:click="selectTable('{{ $row['from_table'] }}')"
                            class="font-mono text-primary-600 hover:underline dark:text-primary-400"
                        >{{ $row['from_table'] }}</button>
                        <span class="font-mono text-xs">({{ implode(',', $row['from_columns']) }})</span>
                        <span class="text-gray-400">→</span>
                        <span class="font-mono text-xs">{{ implode(',', $row['to_columns']) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Indexes --}}
    @if (! empty($table['indexes']))
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold dark:border-white/10">Indexes</div>
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($table['indexes'] as $idx)
                    <li class="flex flex-wrap items-center gap-2 px-4 py-2 text-sm">
                        <span class="font-mono text-xs">{{ $idx['name'] }}</span>
                        <span class="font-mono text-xs text-gray-500">({{ implode(', ', $idx['columns']) }})</span>
                        <div class="ml-auto flex gap-1">
                            @if ($idx['primary'])
                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">PRIMARY</span>
                            @endif
                            @if ($idx['unique'] && ! $idx['primary'])
                                <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">UNIQUE</span>
                            @endif
                            @if ($idx['type'])
                                <span class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-mono text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ strtoupper($idx['type']) }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
