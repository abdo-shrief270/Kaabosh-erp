<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use Illuminate\Support\Str;

/**
 * Convert a {@see DatabaseSchemaService} snapshot to Mermaid `erDiagram` source.
 *
 * Mermaid identifiers are constrained to alphanumerics + underscore; table names
 * containing dots, dashes, or schema prefixes are sanitized and aliased back via
 * the entity label.
 */
class MermaidErdRenderer
{
    /**
     * @param  array<string, mixed>  $snapshot  output of DatabaseSchemaService::snapshot()
     * @param  list<string>|null  $onlyGroups  if provided, only include tables from these groups
     * @param  list<string>|null  $onlyTables  if provided, only include these tables (overrides $onlyGroups)
     */
    public function render(array $snapshot, ?array $onlyGroups = null, ?array $onlyTables = null): string
    {
        $allowed = $this->collectAllowedTables($snapshot, $onlyGroups, $onlyTables);

        $lines = ['erDiagram'];

        foreach ($snapshot['groups'] as $tables) {
            foreach ($tables as $table) {
                if (! in_array($table['name'], $allowed, true)) {
                    continue;
                }

                $lines[] = $this->renderEntity($table);
            }
        }

        $lines[] = '';

        foreach ($snapshot['groups'] as $tables) {
            foreach ($tables as $table) {
                if (! in_array($table['name'], $allowed, true)) {
                    continue;
                }

                foreach ($table['foreign_keys'] as $fk) {
                    $foreignTable = $fk['foreign_table'] ?? null;

                    if ($foreignTable === null || ! in_array($foreignTable, $allowed, true)) {
                        continue;
                    }

                    $lines[] = $this->renderRelation($table['name'], $fk);
                }
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<string>|null  $onlyGroups
     * @param  list<string>|null  $onlyTables
     * @return list<string>
     */
    private function collectAllowedTables(array $snapshot, ?array $onlyGroups, ?array $onlyTables): array
    {
        $allowed = [];

        foreach ($snapshot['groups'] as $group => $tables) {
            foreach ($tables as $table) {
                if ($onlyTables !== null) {
                    if (in_array($table['name'], $onlyTables, true)) {
                        $allowed[] = $table['name'];
                    }
                    continue;
                }

                if ($onlyGroups !== null && ! in_array($group, $onlyGroups, true)) {
                    continue;
                }

                $allowed[] = $table['name'];
            }
        }

        return $allowed;
    }

    /** @param  array<string, mixed>  $table */
    private function renderEntity(array $table): string
    {
        $entity = $this->safeId($table['name']);
        $primaryKey = $table['primary_key'] ?? [];

        $uniqueColumns = [];
        foreach ($table['indexes'] ?? [] as $index) {
            if (! empty($index['unique']) && empty($index['primary']) && count($index['columns']) === 1) {
                $uniqueColumns[] = $index['columns'][0];
            }
        }

        $fkColumns = [];
        foreach ($table['foreign_keys'] ?? [] as $fk) {
            foreach ($fk['columns'] as $col) {
                $fkColumns[] = $col;
            }
        }

        $lines = [];
        $lines[] = '    '.$entity.' {';

        foreach ($table['columns'] as $column) {
            $type = $this->prettyType($column);
            $name = $this->safeId($column['name']);

            $tags = [];
            if (in_array($column['name'], $primaryKey, true)) {
                $tags[] = 'PK';
            }
            if (in_array($column['name'], $fkColumns, true)) {
                $tags[] = 'FK';
            }
            if (in_array($column['name'], $uniqueColumns, true)) {
                $tags[] = 'UK';
            }

            $tagSuffix = $tags === [] ? '' : ' '.implode(',', $tags);

            $note = [];
            if (! empty($column['nullable'])) {
                $note[] = 'nullable';
            }
            if (! empty($column['auto_increment'])) {
                $note[] = 'auto';
            } elseif ($column['default'] !== null && $column['default'] !== '') {
                $default = $this->prettyDefault($column['default']);
                if ($default !== null) {
                    $note[] = 'default='.$default;
                }
            }

            $comment = $note === [] ? '' : ' "'.str_replace('"', "'", implode(', ', $note)).'"';

            $lines[] = sprintf('        %s %s%s%s', $type, $name, $tagSuffix, $comment);
        }

        $lines[] = '    }';

        return implode("\n", $lines);
    }

    /** @param  array<string, mixed>  $fk */
    private function renderRelation(string $fromTable, array $fk): string
    {
        $from = $this->safeId($fromTable);
        $to = $this->safeId($fk['foreign_table']);

        // Cardinality: nullable FK → zero-or-one on the child side, otherwise one-or-more.
        // Mermaid syntax: parent ||--o{ child (one to many, optional child).
        $relation = '||--o{';

        $label = is_array($fk['columns']) && $fk['columns'] !== []
            ? implode(',', $fk['columns'])
            : 'fk';

        return sprintf('    %s %s %s : "%s"', $to, $relation, $from, $label);
    }

    private function safeId(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '_', $name) ?? $name;

        return $clean === '' ? 'tbl' : $clean;
    }

    /** @param  array<string, mixed>  $column */
    private function prettyType(array $column): string
    {
        // Prefer the short type_name (e.g. "varchar") over the verbose type
        // ("character varying(500)"). Append precision only for varchar/numeric.
        $base = (string) ($column['type_name'] ?? $column['type'] ?? 'unknown');
        $base = strtolower($base);

        $aliases = [
            'character varying' => 'varchar',
            'character' => 'char',
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamptz',
            'time without time zone' => 'time',
            'double precision' => 'double',
        ];
        $base = $aliases[$base] ?? $base;

        $full = (string) ($column['type'] ?? '');
        if (preg_match('/\(([^)]+)\)/', $full, $m) === 1
            && in_array($base, ['varchar', 'char', 'numeric', 'decimal'], true)) {
            $base .= '_'.$m[1];
        }

        $clean = preg_replace('/[^A-Za-z0-9_]/', '_', $base) ?? 'unknown';

        return $clean === '' ? 'unknown' : $clean;
    }

    private function prettyDefault(mixed $default): ?string
    {
        if (! is_scalar($default)) {
            return null;
        }

        $str = (string) $default;

        if (str_contains($str, 'nextval(')) {
            return null; // sequence default → already "auto"
        }

        // Strip Postgres type casts ("'EGP'::character varying" → 'EGP').
        $str = preg_replace('/::[a-zA-Z_ ]+(\[\])?/', '', $str) ?? $str;

        $str = trim($str, " '\"");
        $str = str_replace(['"', "'"], '', $str);

        return Str::limit($str, 20, '…');
    }
}
