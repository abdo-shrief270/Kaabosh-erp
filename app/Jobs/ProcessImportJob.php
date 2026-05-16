<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Import\Models\ImportJob;
use App\Domain\Import\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600; // 10 minutes max

    public function __construct(
        public readonly int $importJobId,
    ) {
        $this->onQueue('default');
    }

    public function handle(ImportService $service): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (! $importJob || $importJob->status !== 'pending') {
            return;
        }

        $service->process($importJob);
    }

    public function failed(\Throwable $exception): void
    {
        $importJob = ImportJob::find($this->importJobId);
        $importJob?->markFailed($exception->getMessage());
    }
}
