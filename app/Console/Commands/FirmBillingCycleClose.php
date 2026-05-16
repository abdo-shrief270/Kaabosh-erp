<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Firm\Models\Firm;
use App\Domain\Firm\Services\FirmInvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Generates last month's invoice for every firm. Idempotent — re-running
 * the same day returns the existing rows without creating duplicates. The
 * scheduler runs this on the 1st of each month at 02:00 UTC.
 */
class FirmBillingCycleClose extends Command
{
    protected $signature = 'firm:billing:cycle-close
        {--month= : YYYY-MM period to close, defaults to last month}
        {--firm= : Only process a single firm id}
        {--dry-run : Compute totals without creating invoices}';

    protected $description = 'Generate firm-level invoices for the closed billing period';

    public function handle(FirmInvoiceService $invoices): int
    {
        $monthArg = $this->option('month');
        $period = $monthArg
            ? CarbonImmutable::parse($monthArg.'-01', 'UTC')
            : CarbonImmutable::now('UTC')->subMonthNoOverflow()->startOfMonth();

        $firmId = $this->option('firm');
        $query = Firm::query()->where('status', 'active');
        if ($firmId) {
            $query->where('id', (int) $firmId);
        }

        $firms = $query->get();
        $this->info("Closing period {$period->format('Y-m')} for {$firms->count()} firm(s)…");

        $generated = 0;
        $skipped   = 0;
        $totalEgp  = 0;

        foreach ($firms as $firm) {
            try {
                if ($this->option('dry-run')) {
                    $this->line("  [dry-run] firm #{$firm->id} ({$firm->name})");
                    $skipped++;
                    continue;
                }

                $invoice = $invoices->generateForPeriod($firm, $period);
                $totalEgp += $invoice->total_amount;

                $existed = ! $invoice->wasRecentlyCreated;
                $this->line(sprintf(
                    '  %s firm #%d (%s) %s — %d EGP',
                    $existed ? '·' : '✓',
                    $firm->id,
                    $firm->name,
                    $invoice->invoice_number,
                    $invoice->total_amount,
                ));
                if ($existed) $skipped++; else $generated++;
            } catch (\Throwable $e) {
                Log::error('firm cycle-close failed', [
                    'firm_id' => $firm->id,
                    'period'  => $period->format('Y-m'),
                    'error'   => $e->getMessage(),
                ]);
                $this->error("  ✗ firm #{$firm->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Generated: {$generated} | Already-existed: {$skipped} | Total billed: {$totalEgp} EGP");
        return self::SUCCESS;
    }
}
