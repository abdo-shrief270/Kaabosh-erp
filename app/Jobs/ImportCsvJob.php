<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Services\CsvImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportCsvJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $filePath,
        private readonly string $importType,
        private readonly int $tenantId,
    ) {}

    public function handle(CsvImportService $service): void
    {
        $file = new \SplFileInfo($this->filePath);

        match ($this->importType) {
            'clients' => $service->importClients($file, $this->tenantId),
            'accounts' => $service->importAccounts($file, $this->tenantId),
            'opening_balances' => $service->importOpeningBalances($file, $this->tenantId),
        };

        // Clean up temp file
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
