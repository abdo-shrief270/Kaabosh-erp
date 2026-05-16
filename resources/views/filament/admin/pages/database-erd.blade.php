<x-filament-panels::page>
    @php
        $snapshot = $this->snapshot;
        $totals = $snapshot['totals'];
        $groups = $snapshot['groups'];
        $visible = $this->visibleTables;
        $focused = $this->focusedTable;
        $incoming = $this->incomingForeignKeys;
        $hasMermaid = $this->mermaidAssetExists;
    @endphp

    <div class="space-y-4">
        {{-- Header summary --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Connection</div>
                <div class="font-mono text-sm">{{ $snapshot['connection'] }} · {{ $snapshot['driver'] }}</div>
                <div class="truncate text-xs text-gray-500">{{ $snapshot['database'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Tables</div>
                <div class="text-2xl font-semibold">{{ number_format($totals['tables']) }}</div>
            </div>
            <div class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Columns</div>
                <div class="text-2xl font-semibold">{{ number_format($totals['columns']) }}</div>
            </div>
            <div class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Foreign keys</div>
                <div class="text-2xl font-semibold">{{ number_format($totals['foreign_keys']) }}</div>
            </div>
            <div class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Indexes</div>
                <div class="text-2xl font-semibold">{{ number_format($totals['indexes']) }}</div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-white/10">
            @foreach ([
                'browse' => 'Browse',
                'diagram' => 'Diagram',
            ] as $key => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'border-b-2 px-3 py-2 text-sm font-medium transition',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $tab === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach

            <div class="ml-auto text-xs text-gray-500">
                Snapshot: {{ \Illuminate\Support\Carbon::parse($snapshot['fetched_at'])->diffForHumans() }}
            </div>
        </div>

        @if ($tab === 'browse')
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                {{-- Left: filters + table list --}}
                <aside class="lg:col-span-4 xl:col-span-3">
                    <div class="space-y-3 rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Search tables or columns…"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950"
                        />

                        <details class="group">
                            <summary class="cursor-pointer select-none text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Filter by group ({{ count($groups) }})
                            </summary>
                            <div class="mt-2 space-y-1 max-h-48 overflow-y-auto pr-1">
                                @foreach (array_keys($groups) as $g)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedGroups"
                                            value="{{ $g }}"
                                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        />
                                        <span>{{ $g }}</span>
                                        <span class="ml-auto text-xs text-gray-400">{{ count($groups[$g]) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>

                        <div class="max-h-[60vh] overflow-y-auto">
                            @php($currentGroup = null)
                            @forelse ($visible as $row)
                                @if ($currentGroup !== $row['group'])
                                    @php($currentGroup = $row['group'])
                                    <div class="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $currentGroup }}</div>
                                @endif
                                <button
                                    type="button"
                                    wire:click="selectTable('{{ $row['table']['name'] }}')"
                                    @class([
                                        'flex w-full items-center justify-between rounded px-2 py-1 text-left text-sm',
                                        'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $selectedTable === $row['table']['name'],
                                        'hover:bg-gray-100 dark:hover:bg-white/5' => $selectedTable !== $row['table']['name'],
                                    ])
                                >
                                    <span class="truncate font-mono">{{ $row['table']['name'] }}</span>
                                    <span class="text-xs text-gray-400">{{ count($row['table']['columns']) }}</span>
                                </button>
                            @empty
                                <p class="py-6 text-center text-sm text-gray-500">No tables match.</p>
                            @endforelse
                        </div>
                    </div>
                </aside>

                {{-- Right: detail panel --}}
                <section class="lg:col-span-8 xl:col-span-9">
                    @if ($focused === null)
                        <div class="rounded-lg bg-white p-8 text-center text-sm text-gray-500 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            Select a table on the left to inspect its columns, foreign keys, and indexes.
                        </div>
                    @else
                        @include('filament.admin.pages.database-erd-detail', [
                            'table' => $focused,
                            'incoming' => $incoming,
                        ])
                    @endif
                </section>
            </div>
        @elseif ($tab === 'diagram')
            @php($size = $this->diagramSize)
            @php($isHuge = $size['tables'] > 40)
            @php($scopeReady = $this->scopeReady)

            <div class="space-y-3">
                @if (! $hasMermaid)
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        <div class="font-semibold">Mermaid asset not found.</div>
                        <p class="mt-1">
                            Place <code class="font-mono">mermaid.min.js</code> at
                            <code class="font-mono">public/vendor/mermaid/mermaid.min.js</code> to enable the visual diagram.
                            The raw Mermaid source is shown below regardless.
                        </p>
                    </div>
                @endif

                {{-- Scope picker --}}
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Scope</span>
                    @foreach ([
                        'focus' => 'Focused table + neighbors',
                        'groups' => 'Selected groups',
                        'all' => 'Whole database',
                    ] as $key => $label)
                        <button
                            type="button"
                            wire:click="setDiagramScope('{{ $key }}')"
                            @class([
                                'rounded-full border px-3 py-1 text-xs transition',
                                'border-primary-500 bg-primary-500 text-white' => $diagramScope === $key,
                                'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5' => $diagramScope !== $key,
                            ])
                        >{{ $label }}</button>
                    @endforeach

                    @if ($diagramScope === 'focus')
                        <select
                            wire:change="selectTable($event.target.value)"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950"
                        >
                            <option value="">— pick a table —</option>
                            @foreach ($this->allTableNames as $tname)
                                <option value="{{ $tname }}" @selected($selectedTable === $tname)>{{ $tname }}</option>
                            @endforeach
                        </select>
                    @endif

                    <div class="ml-auto flex items-center gap-3">
                        <span class="text-xs text-gray-500">
                            {{ $size['tables'] }} tables · {{ $size['relations'] }} relations
                        </span>
                        <button
                            type="button"
                            wire:click="renderDiagram"
                            @disabled(! $scopeReady)
                            wire:loading.attr="disabled"
                            class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            {{ $diagramRendered ? 'Re-render' : 'Render diagram' }}
                        </button>
                    </div>
                </div>

                @if (! $scopeReady)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        @if ($diagramScope === 'focus')
                            Pick a table from the <button type="button" wire:click="setTab('browse')" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Browse</button> tab — the diagram will show it plus every table it touches via foreign keys.
                        @else
                            Select at least one group on the Browse tab, then come back and click <strong>Render diagram</strong>.
                        @endif
                    </div>
                @elseif ($isHuge && ! $diagramRendered)
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        <div class="font-semibold">Heads up: {{ $size['tables'] }} tables in scope.</div>
                        <p class="mt-1">
                            Mermaid can struggle past ~40 entities — rendering may take 10-30 seconds and the diagram will be hard to read. Click <strong>Render diagram</strong> to proceed anyway, or narrow the scope.
                        </p>
                    </div>
                @elseif ($hasMermaid && $diagramRendered)
                    @php($diagramKey = md5($this->mermaidSource))
                    <script src="{{ asset('vendor/mermaid/mermaid.min.js') }}"></script>
                    <div
                        wire:ignore
                        wire:key="erd-{{ $diagramKey }}"
                        class="rounded-lg bg-white p-3 shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                    >
                        <div id="erd-status-{{ $diagramKey }}" class="mb-2 text-xs text-gray-500">
                            Initialising…
                        </div>
                        <div id="erd-canvas-{{ $diagramKey }}" class="overflow-auto" style="min-height: 240px;">
                            <noscript class="text-sm text-rose-600">JavaScript is required to render the diagram.</noscript>
                        </div>
                    </div>
                    <script>
                        (function () {
                            const key = @js($diagramKey);
                            const source = @js($this->mermaidSource);
                            const status = document.getElementById('erd-status-' + key);
                            const canvas = document.getElementById('erd-canvas-' + key);
                            const setStatus = (msg, err = false) => {
                                if (! status) return;
                                status.textContent = msg;
                                status.className = err
                                    ? 'mb-2 text-xs text-rose-600 dark:text-rose-400'
                                    : 'mb-2 text-xs text-gray-500';
                            };

                            if (! window.mermaid) {
                                setStatus('mermaid.min.js loaded but window.mermaid is undefined.', true);
                                return;
                            }

                            try {
                                if (! window.__mermaidInit) {
                                    window.mermaid.initialize({
                                        startOnLoad: false,
                                        theme: 'default',
                                        securityLevel: 'loose',
                                        er: { useMaxWidth: false, layoutDirection: 'LR' },
                                    });
                                    window.__mermaidInit = true;
                                }
                            } catch (e) {
                                setStatus('mermaid.initialize failed: ' + (e && e.message || e), true);
                                return;
                            }

                            setStatus('Rendering ' + source.length + ' bytes of Mermaid source…');
                            const id = 'erd-svg-' + key;

                            window.mermaid.render(id, source)
                                .then((res) => {
                                    canvas.innerHTML = res.svg;
                                    setStatus('Rendered ✓ — version ' + (window.mermaid.version || 'unknown'));
                                })
                                .catch((err) => {
                                    setStatus('Render failed: ' + (err && err.message || err), true);
                                    const pre = document.createElement('pre');
                                    pre.style.cssText = 'white-space:pre-wrap;color:#b91c1c;font-size:11px;padding:8px;border:1px solid #fecaca;background:#fef2f2;border-radius:6px;';
                                    pre.textContent = (err && err.stack) || String(err);
                                    canvas.innerHTML = '';
                                    canvas.appendChild(pre);
                                });
                        })();
                    </script>
                @endif

                <details class="rounded-lg bg-white shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" {{ $hasMermaid && $scopeReady ? '' : 'open' }}>
                    <summary class="cursor-pointer px-3 py-2 text-sm font-semibold">Mermaid source ({{ strlen($this->mermaidSource) }} bytes)</summary>
                    <pre class="max-h-96 overflow-auto px-3 pb-3 text-xs leading-snug"><code>{{ $this->mermaidSource }}</code></pre>
                </details>
            </div>
        @endif
    </div>
</x-filament-panels::page>
