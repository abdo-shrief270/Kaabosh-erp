<?php

declare(strict_types=1);

namespace App\Domain\Tax\Services;

use App\Domain\AccountsPayable\Enums\BillStatus;
use App\Domain\AccountsPayable\Models\Bill;
use App\Domain\AccountsPayable\Models\BillPayment;
use App\Domain\Tax\Enums\WhtCertificateStatus;
use App\Domain\Tax\Models\WhtCertificate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WhtCertificateService
{
    /**
     * List WHT certificates with vendor eager load, filtered by vendor/status/period.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = WhtCertificate::query()->with('vendor');

        if (! empty($filters['vendor_id'])) {
            $query->forVendor((int) $filters['vendor_id']);
        }

        if (! empty($filters['status'])) {
            $query->ofStatus(WhtCertificateStatus::from($filters['status']));
        }

        if (! empty($filters['period_from']) && ! empty($filters['period_to'])) {
            $query->forPeriod($filters['period_from'], $filters['period_to']);
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Auto-calculate WHT from bill_payments in period for a vendor.
     * Sums WHT amounts from bills using bcmath and creates a certificate with calculated totals.
     */
    public function generate(int $vendorId, string $from, string $to): WhtCertificate
    {
        // Get all bill payments for this vendor in the period
        $payments = BillPayment::query()
            ->forVendor($vendorId)
            ->dateRange($from, $to)
            ->with('bill')
            ->get();

        $totalPayments = '0.00';
        $totalWht = '0.00';
        $billsBreakdown = [];

        foreach ($payments as $payment) {
            $bill = $payment->bill;

            if (! $bill || $bill->status === BillStatus::Cancelled) {
                continue;
            }

            $paymentAmount = (string) $payment->amount;
            $totalPayments = bcadd($totalPayments, $paymentAmount, 2);

            // WHT is proportional: (payment / bill total) * bill wht_amount
            $billTotal = (string) $bill->total;
            $billWht = (string) $bill->wht_amount;

            if (bccomp($billTotal, '0', 2) > 0 && bccomp($billWht, '0', 2) > 0) {
                $proportion = bcdiv($paymentAmount, $billTotal, 8);
                $whtForPayment = bcmul($proportion, $billWht, 2);
                $totalWht = bcadd($totalWht, $whtForPayment, 2);

                $billsBreakdown[] = [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'bill_date' => $bill->date->format('Y-m-d'),
                    'bill_total' => $billTotal,
                    'bill_wht' => $billWht,
                    'payment_id' => $payment->id,
                    'payment_amount' => $paymentAmount,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'wht_amount' => $whtForPayment,
                ];
            }
        }

        return WhtCertificate::create([
            'vendor_id' => $vendorId,
            'period_from' => $from,
            'period_to' => $to,
            'total_payments' => $totalPayments,
            'total_wht' => $totalWht,
            'status' => WhtCertificateStatus::Draft,
            'data' => [
                'bills_breakdown' => $billsBreakdown,
                'payments_count' => $payments->count(),
                'generated_at' => now()->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Set status to issued, assign issued_at and generate certificate number (WHT-YYYY-NNNNNN).
     */
    public function issue(WhtCertificate $cert): WhtCertificate
    {
        return DB::transaction(function () use ($cert): WhtCertificate {
            $year = now()->format('Y');

            $lastNumber = WhtCertificate::query()
                ->where('certificate_number', 'like', "WHT-{$year}-%")
                ->orderByDesc('certificate_number')
                ->value('certificate_number');

            $sequence = 1;

            if ($lastNumber) {
                $parts = explode('-', $lastNumber);
                $sequence = ((int) end($parts)) + 1;
            }

            $certificateNumber = sprintf('WHT-%s-%06d', $year, $sequence);

            $cert->update([
                'status' => WhtCertificateStatus::Issued,
                'issued_at' => now(),
                'certificate_number' => $certificateNumber,
            ]);

            return $cert->refresh();
        });
    }

    /**
     * Mark certificate as submitted to tax authority.
     */
    public function submit(WhtCertificate $cert): WhtCertificate
    {
        $cert->update([
            'status' => WhtCertificateStatus::Submitted,
            'submitted_at' => now(),
        ]);

        return $cert->refresh();
    }

    /**
     * Load certificate with vendor details and bills breakdown.
     */
    public function show(WhtCertificate $cert): WhtCertificate
    {
        return $cert->load('vendor');
    }
}
