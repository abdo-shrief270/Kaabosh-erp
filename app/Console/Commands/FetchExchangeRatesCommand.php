<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Currency\Services\CurrencyService;
use Illuminate\Console\Command;

class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'currency:fetch-rates {--base=EGP : Base currency code}';

    protected $description = 'Fetch latest exchange rates from external API';

    public function handle(): int
    {
        $base = strtoupper($this->option('base'));

        $this->info("Fetching exchange rates for {$base}...");

        $count = CurrencyService::fetchRates($base);

        if ($count > 0) {
            $this->info("Successfully fetched {$count} exchange rates.");
        } else {
            $this->warn('No rates fetched. Check API configuration.');
        }

        return self::SUCCESS;
    }
}
