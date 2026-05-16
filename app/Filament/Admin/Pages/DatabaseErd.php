<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Domain\Admin\Services\DatabaseSchemaService;
use App\Domain\Admin\Services\MermaidErdRenderer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Live ERD: introspects the connected database every render
 * (cached 5 min, bustable) and shows tables, columns, FKs, indexes
 * grouped by domain. Optional Mermaid diagram canvas if the vendored
 * mermaid.min.js is present at public/vendor/mermaid/mermaid.min.js.
 *
 * SuperAdmin only — exposes full schema metadata.
 */
class DatabaseErd extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'database-erd';

    protected static ?string $title = 'Database ERD';

    protected string $view = 'filament.admin.pages.database-erd';

    public string $search = '';

    /** @var list<string> */
    public array $selectedGroups = [];

    public ?string $selectedTable = null;

    public string $tab = 'browse';

    /** focus | groups | all */
    public string $diagramScope = 'focus';

    public bool $diagramRendered = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? 'Database ERD';
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function snapshot(): array
    {
        return app(DatabaseSchemaService::class)->snapshot();
    }

    #[Computed]
    public function mermaidSource(): string
    {
        $snapshot = $this->snapshot();
        $renderer = app(MermaidErdRenderer::class);

        if ($this->diagramScope === 'focus' && $this->selectedTable !== null) {
            $tables = app(DatabaseSchemaService::class)->neighborhood($snapshot, $this->selectedTable);

            return $renderer->render($snapshot, null, $tables);
        }

        if ($this->diagramScope === 'groups' && $this->selectedGroups !== []) {
            return $renderer->render($snapshot, $this->selectedGroups);
        }

        if ($this->diagramScope === 'all') {
            return $renderer->render($snapshot);
        }

        return "erDiagram\n";
    }

    /** @return array{tables:int,relations:int} */
    #[Computed]
    public function diagramSize(): array
    {
        $source = $this->mermaidSource;

        return [
            'tables' => substr_count($source, " {\n"),
            'relations' => substr_count($source, '||--'),
        ];
    }

    #[Computed]
    public function scopeReady(): bool
    {
        return match ($this->diagramScope) {
            'focus' => $this->selectedTable !== null,
            'groups' => $this->selectedGroups !== [],
            'all' => true,
            default => false,
        };
    }

    public function setDiagramScope(string $scope): void
    {
        $this->diagramScope = in_array($scope, ['focus', 'groups', 'all'], true) ? $scope : 'focus';
        $this->diagramRendered = false;
        unset($this->mermaidSource, $this->diagramSize);
    }

    public function renderDiagram(): void
    {
        $this->diagramRendered = true;
        unset($this->mermaidSource, $this->diagramSize);
    }

    #[Computed]
    public function mermaidAssetExists(): bool
    {
        return is_file(public_path('vendor/mermaid/mermaid.min.js'));
    }

    /**
     * Tables filtered by search + selected groups, flattened with their group label.
     *
     * @return list<array{group: string, table: array<string, mixed>}>
     */
    #[Computed]
    public function visibleTables(): array
    {
        $needle = mb_strtolower(trim($this->search));
        $visible = [];

        foreach ($this->snapshot()['groups'] as $group => $tables) {
            if ($this->selectedGroups !== [] && ! in_array($group, $this->selectedGroups, true)) {
                continue;
            }

            foreach ($tables as $table) {
                if ($needle !== '' && ! str_contains(mb_strtolower($table['name']), $needle)) {
                    $colMatch = false;
                    foreach ($table['columns'] as $col) {
                        if (str_contains(mb_strtolower($col['name']), $needle)) {
                            $colMatch = true;
                            break;
                        }
                    }
                    if (! $colMatch) {
                        continue;
                    }
                }

                $visible[] = ['group' => $group, 'table' => $table];
            }
        }

        return $visible;
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function focusedTable(): ?array
    {
        if ($this->selectedTable === null) {
            return null;
        }

        foreach ($this->snapshot()['groups'] as $tables) {
            foreach ($tables as $table) {
                if ($table['name'] === $this->selectedTable) {
                    return $table;
                }
            }
        }

        return null;
    }

    /**
     * Foreign keys pointing INTO the focused table (reverse FKs).
     *
     * @return list<array{from_table: string, from_columns: list<string>, to_columns: list<string>}>
     */
    #[Computed]
    public function incomingForeignKeys(): array
    {
        if ($this->selectedTable === null) {
            return [];
        }

        $incoming = [];

        foreach ($this->snapshot()['groups'] as $tables) {
            foreach ($tables as $table) {
                foreach ($table['foreign_keys'] as $fk) {
                    if ($fk['foreign_table'] === $this->selectedTable) {
                        $incoming[] = [
                            'from_table' => $table['name'],
                            'from_columns' => $fk['columns'],
                            'to_columns' => $fk['foreign_columns'],
                        ];
                    }
                }
            }
        }

        return $incoming;
    }

    public function selectTable(string $name): void
    {
        $this->selectedTable = $name;
        $this->diagramRendered = false;
        unset($this->mermaidSource, $this->diagramSize);
    }

    public function clearSelection(): void
    {
        $this->selectedTable = null;
        $this->diagramRendered = false;
        unset($this->mermaidSource, $this->diagramSize);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['browse', 'diagram'], true) ? $tab : 'browse';

        // Make the Diagram tab "just work": pick a small default table on
        // first arrival so the user sees something rendered immediately.
        if ($this->tab === 'diagram' && $this->diagramScope === 'focus' && $this->selectedTable === null) {
            $default = $this->pickDefaultFocus();
            if ($default !== null) {
                $this->selectedTable = $default;
                $this->diagramRendered = true;
                unset($this->mermaidSource, $this->diagramSize);
            }
        }
    }

    /** @return list<string> all table names, alphabetical, for the in-tab picker */
    #[Computed]
    public function allTableNames(): array
    {
        $names = [];
        foreach ($this->snapshot()['groups'] as $tables) {
            foreach ($tables as $t) {
                $names[] = $t['name'];
            }
        }
        sort($names);

        return $names;
    }

    /**
     * Pick a default table that yields a small, useful neighborhood
     * (≤ 15 tables) so the first render is fast and legible.
     */
    private function pickDefaultFocus(): ?string
    {
        $snapshot = $this->snapshot();
        $svc = app(DatabaseSchemaService::class);
        $names = $this->allTableNames;

        $preferred = ['subscriptions', 'invoices', 'engagements', 'employees', 'clients'];

        foreach ($preferred as $candidate) {
            if (! in_array($candidate, $names, true)) {
                continue;
            }
            $size = count($svc->neighborhood($snapshot, $candidate));
            if ($size > 0 && $size <= 15) {
                return $candidate;
            }
        }

        // Fallback: scan and pick the first table with a small, non-empty neighborhood.
        foreach ($names as $name) {
            $size = count($svc->neighborhood($snapshot, $name));
            if ($size >= 2 && $size <= 10) {
                return $name;
            }
        }

        return $names[0] ?? null;
    }

    public function updatedSearch(): void
    {
        unset($this->visibleTables);
    }

    public function updatedSelectedGroups(): void
    {
        $this->diagramRendered = false;
        unset($this->visibleTables, $this->mermaidSource, $this->diagramSize);
    }

    /** @return array<string, Action> */
    protected function getHeaderActions(): array
    {
        return [
            'refresh' => Action::make('refresh')
                ->label('Refresh schema')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->action(function (): void {
                    app(DatabaseSchemaService::class)->flushCache();
                    unset(
                        $this->snapshot,
                        $this->visibleTables,
                        $this->focusedTable,
                        $this->incomingForeignKeys,
                        $this->mermaidSource,
                    );

                    Notification::make()
                        ->title('Schema reloaded from database')
                        ->success()
                        ->send();
                }),

            'download_json' => Action::make('download_json')
                ->label('Download JSON')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->streamJson()),

            'download_mermaid' => Action::make('download_mermaid')
                ->label('Download Mermaid (.mmd)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->streamMermaid()),
        ];
    }

    private function streamJson(): StreamedResponse
    {
        $snapshot = app(DatabaseSchemaService::class)->snapshot();
        $payload = (string) json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = 'db-schema-'.now()->format('Y-m-d_His').'.json';

        return response()->streamDownload(
            static fn () => print($payload),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    private function streamMermaid(): StreamedResponse
    {
        $snapshot = app(DatabaseSchemaService::class)->snapshot();
        $only = $this->selectedGroups === [] ? null : $this->selectedGroups;
        $body = app(MermaidErdRenderer::class)->render($snapshot, $only);
        $filename = 'db-erd-'.now()->format('Y-m-d_His').'.mmd';

        return response()->streamDownload(
            static fn () => print($body),
            $filename,
            ['Content-Type' => 'text/plain'],
        );
    }
}
