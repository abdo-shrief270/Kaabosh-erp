<?php

declare(strict_types=1);

namespace App\Domain\Firm\Services;

use App\Domain\Firm\Models\Firm;
use App\Domain\Firm\Models\FirmInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Generates firm-level monthly invoices. One invoice = one calendar month
 * per firm. Idempotent via the (firm_id, period_start) unique constraint —
 * calling generateForPeriod twice for the same month returns the existing row.
 *
 * The invoice snapshots the current billing breakdown so historical bills
 * stay accurate even if the firm later changes plan or deactivates clients.
 */
class FirmInvoiceService
{
    public function __construct(
        private readonly FirmBillingService $billing,
    ) {}

    public function generateForPeriod(Firm $firm, CarbonImmutable $month): FirmInvoice
    {
        $start = $month->startOfMonth();
        $end   = $month->endOfMonth();

        $existing = FirmInvoice::where('firm_id', $firm->id)
            ->whereDate('period_start', $start->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $breakdown = $this->billing->breakdown($firm);

        return DB::transaction(function () use ($firm, $start, $end, $breakdown): FirmInvoice {
            return FirmInvoice::create([
                'firm_id'        => $firm->id,
                'invoice_number' => $this->nextInvoiceNumber($firm, $start),
                'period_start'   => $start->toDateString(),
                'period_end'     => $end->toDateString(),
                'plan_tier'      => $breakdown['base']['tier'],
                'base_amount'    => (int) ($breakdown['totals']['base'] ?? 0),
                'clients_amount' => (int) ($breakdown['totals']['clients'] ?? 0),
                'total_amount'   => (int) ($breakdown['totals']['monthly_total'] ?? 0),
                'line_items'     => $breakdown['clients'],
                'status'         => 'pending',
            ]);
        });
    }

    /**
     * Mark an invoice paid. Records who, when, and how (payment_method /
     * payment_reference). The firm.subscription_ends_at is bumped to the
     * end of the next billing period so feature gates stay open.
     */
    public function markPaid(FirmInvoice $invoice, int $userId, string $method, ?string $reference = null): FirmInvoice
    {
        return DB::transaction(function () use ($invoice, $userId, $method, $reference): FirmInvoice {
            $invoice->update([
                'status'            => 'paid',
                'paid_at'           => now(),
                'payment_method'    => $method,
                'payment_reference' => $reference,
                'paid_by_user_id'   => $userId,
            ]);

            $firm = $invoice->firm;
            // Extend the firm's subscription end date through the next period
            $nextEnd = CarbonImmutable::parse($invoice->period_end)->addMonth()->endOfMonth();
            if (! $firm->subscription_ends_at || $firm->subscription_ends_at->lt($nextEnd)) {
                $firm->subscription_ends_at = $nextEnd;
                $firm->save();
            }

            return $invoice->fresh();
        });
    }

    private function nextInvoiceNumber(Firm $firm, CarbonImmutable $start): string
    {
        return sprintf('FRM-%d-%s', $firm->id, $start->format('Ym'));
    }
}
