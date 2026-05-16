<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'backup:database
        {--disk=local : Storage disk to save backup}
        {--keep=30 : Number of days to keep old backups}';

    protected $description = 'Create a database backup and optionally clean old backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! in_array($connection, ['mysql', 'mariadb', 'pgsql'])) {
            $this->warn("Backup only supports MySQL/MariaDB/PostgreSQL. Current driver: {$connection}");

            return self::FAILURE;
        }

        $filename = 'backup_'.Carbon::now()->format('Y-m-d_His').'.sql.gz';
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = $backupDir.'/'.$filename;

        if ($connection === 'pgsql') {
            $which = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump 2>nul' : 'which pg_dump 2>/dev/null';
            if (empty(trim((string) shell_exec($which)))) {
                $this->error('pg_dump binary not found.');

                return 1;
            }

            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port", 5432);
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            $command = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -Fc %s > %s',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($path)
            );
        } else {
            $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null';
            if (empty(trim((string) shell_exec($which)))) {
                $this->error('mysqldump binary not found.');

                return 1;
            }

            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'];
            $username = $config['username'];
            $password = $config['password'] ?? '';

            $passwordPart = $password ? '--password='.escapeshellarg($password) : '';

            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers %s | gzip > %s',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $passwordPart,
                escapeshellarg($database),
                escapeshellarg($path),
            );
        }

        $this->info("Backing up database '{$database}'...");

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Backup failed. Ensure mysqldump is installed and credentials are correct.');

            return self::FAILURE;
        }

        $size = round(filesize($path) / 1024 / 1024, 2);
        $this->info("Backup created: {$filename} ({$size} MB)");

        // Clean old backups
        $keep = (int) $this->option('keep');
        $this->cleanOldBackups($backupDir, $keep);

        return self::SUCCESS;
    }

    private function cleanOldBackups(string $dir, int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $count = 0;

        foreach (glob($dir.'/backup_*.sql.gz') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("Cleaned {$count} old backup(s) older than {$keepDays} days.");
        }
    }
}
