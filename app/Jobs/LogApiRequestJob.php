<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class LogApiRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $logData,
    ) {}

    public function handle(): void
    {
        DB::table('api_request_logs')->insert($this->logData);
    }
}
