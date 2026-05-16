<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Live database introspection.
 *
 * Reads the actual connected database (not Eloquent metadata) so the result
 * includes every table — pivots, queue, cache, sessions, migrations — and
 * reflects every column, FK, and index exactly as it exists on disk.
 */
class DatabaseSchemaService
{
    private const CACHE_KEY = 'admin:db-schema:v1';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Full schema snapshot, grouped by domain.
     *
     * @return array{
     *     connection: string,
     *     driver: string,
     *     database: string,
     *     groups: array<string, list<array{name: string, columns: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>, indexes: list<array<string, mixed>>, primary_key: list<string>, comment: ?string}>>,
     *     totals: array{tables: int, columns: int, foreign_keys: int, indexes: int},
     *     fetched_at: string,
     * }
     */
    public function snapshot(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildSnapshot(),
        );
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Tables in the FK neighborhood of $table — itself, all tables it
     * references, and all tables that reference it (1 hop).
     *
     * @param  array<string, mixed>  $snapshot
     * @return list<string>
     */
    public function neighborhood(array $snapshot, string $table): array
    {
        $result = [$table];

        foreach ($snapshot['groups'] as $tables) {
            foreach ($tables as $t) {
                if ($t['name'] === $table) {
                    foreach ($t['foreign_keys'] as $fk) {
                        if (! empty($fk['foreign_table'])) {
                            $result[] = $fk['foreign_table'];
                        }
                    }
                    continue;
                }

                foreach ($t['foreign_keys'] as $fk) {
                    if (($fk['foreign_table'] ?? null) === $table) {
                        $result[] = $t['name'];
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($result));
    }

    /** @return array<string, mixed> */
    private function buildSnapshot(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = (string) $connection->getDatabaseName();

        $tableMeta = Schema::getTables();
        $domainMap = $this->buildDomainMap();

        $groups = [];
        $totals = ['tables' => 0, 'columns' => 0, 'foreign_keys' => 0, 'indexes' => 0];

        foreach ($tableMeta as $meta) {
            $tableName = $meta['name'];

            if ($this->shouldSkipTable($tableName)) {
                continue;
            }

            $columns = $this->normalizeColumns(Schema::getColumns($tableName));
            $foreignKeys = $this->normalizeForeignKeys(Schema::getForeignKeys($tableName));
            $indexes = $this->normalizeIndexes(Schema::getIndexes($tableName));

            $primaryKey = collect($indexes)
                ->firstWhere('primary', true)['columns'] ?? [];

            $group = $domainMap[$tableName] ?? $this->inferGroup($tableName);

            $groups[$group] ??= [];
            $groups[$group][] = [
                'name' => $tableName,
                'columns' => $columns,
                'foreign_keys' => $foreignKeys,
                'indexes' => $indexes,
                'primary_key' => $primaryKey,
                'comment' => $meta['comment'] ?? null,
            ];

            $totals['tables']++;
            $totals['columns'] += count($columns);
            $totals['foreign_keys'] += count($foreignKeys);
            $totals['indexes'] += count($indexes);
        }

        ksort($groups);
        foreach ($groups as $g => $tables) {
            usort($groups[$g], static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        }

        return [
            'connection' => $connection->getName(),
            'driver' => $driver,
            'database' => $database,
            'groups' => $groups,
            'totals' => $totals,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    private function normalizeColumns(array $columns): array
    {
        return array_values(array_map(static fn (array $col): array => [
            'name' => $col['name'],
            'type' => $col['type'] ?? $col['type_name'] ?? 'unknown',
            'type_name' => $col['type_name'] ?? null,
            'nullable' => (bool) ($col['nullable'] ?? false),
            'default' => $col['default'] ?? null,
            'auto_increment' => (bool) ($col['auto_increment'] ?? false),
            'comment' => $col['comment'] ?? null,
        ], $columns));
    }

    /**
     * @param  list<array<string, mixed>>  $fks
     * @return list<array<string, mixed>>
     */
    private function normalizeForeignKeys(array $fks): array
    {
        return array_values(array_map(static fn (array $fk): array => [
            'name' => $fk['name'] ?? null,
            'columns' => array_values((array) ($fk['columns'] ?? [])),
            'foreign_table' => $fk['foreign_table'] ?? null,
            'foreign_columns' => array_values((array) ($fk['foreign_columns'] ?? [])),
            'on_update' => $fk['on_update'] ?? null,
            'on_delete' => $fk['on_delete'] ?? null,
        ], $fks));
    }

    /**
     * @param  list<array<string, mixed>>  $indexes
     * @return list<array<string, mixed>>
     */
    private function normalizeIndexes(array $indexes): array
    {
        return array_values(array_map(static fn (array $idx): array => [
            'name' => $idx['name'] ?? null,
            'columns' => array_values((array) ($idx['columns'] ?? [])),
            'type' => $idx['type'] ?? null,
            'unique' => (bool) ($idx['unique'] ?? false),
            'primary' => (bool) ($idx['primary'] ?? false),
        ], $indexes));
    }

    private function shouldSkipTable(string $name): bool
    {
        return in_array($name, ['migrations'], true);
    }

    /**
     * Build a map of table_name => domain group label by scanning
     * `app/Domain/*\/Models/*.php` and asking each model for its table.
     *
     * @return array<string, string>
     */
    private function buildDomainMap(): array
    {
        $map = [];
        $domainBase = app_path('Domain');

        if (! File::isDirectory($domainBase)) {
            return $map;
        }

        foreach (File::directories($domainBase) as $domainDir) {
            $domain = basename($domainDir);
            $modelsDir = $domainDir.DIRECTORY_SEPARATOR.'Models';

            if (! File::isDirectory($modelsDir)) {
                continue;
            }

            foreach (File::files($modelsDir) as $file) {
                $class = 'App\\Domain\\'.$domain.'\\Models\\'.$file->getFilenameWithoutExtension();

                if (! class_exists($class)) {
                    continue;
                }

                try {
                    $instance = new $class;
                    if (! method_exists($instance, 'getTable')) {
                        continue;
                    }
                    $table = $instance->getTable();
                    $map[$table] ??= $domain;
                } catch (Throwable) {
                    // Skip models that can't be instantiated bare (e.g. require constructor args).
                }
            }
        }

        return $map;
    }

    /**
     * Fallback grouping for tables not owned by any domain model
     * (queue/jobs/cache/sessions/permissions/pivots, etc).
     */
    private function inferGroup(string $table): string
    {
        $patterns = [
            'System' => ['/^cache/', '/^sessions$/', '/^password_/', '/^failed_jobs$/', '/^job_batches$/', '/^jobs$/', '/^queue_/', '/^pulse_/', '/^telescope_/', '/^horizon_/'],
            'Auth & Permissions' => ['/^users$/', '/^model_has_/', '/^role_has_/', '/^permissions$/', '/^roles$/', '/^personal_access_tokens$/', '/^oauth_/', '/^two_factor/'],
            'Notifications' => ['/^notifications$/', '/^notification_/'],
            'Activity & Audit' => ['/^activity_log$/', '/^audit_/'],
            'Media' => ['/^media$/'],
            'Webhooks' => ['/^webhook/'],
        ];

        foreach ($patterns as $group => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $table) === 1) {
                    return $group;
                }
            }
        }

        // Pivot guess: contains "_" and no domain claimed it.
        return Str::contains($table, '_') ? 'Pivots & Misc' : 'Other';
    }
}
