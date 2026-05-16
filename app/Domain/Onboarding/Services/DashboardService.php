<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Domain\Accounting\Enums\JournalEntryStatus;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Client\Models\Client;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    /**
     * Return dashboard KPIs for the current tenant.
     *
     * @return array<string, mixed>
     */
    public function getKpis(?int $tenantId = null): array
    {
        $tenantId ??= (int) app('tenant.id');
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return [
            'clients' => $this->getClientKpis($tenantId, $startOfMonth, $endOfMonth),
            'invoices' => $this->getInvoiceKpis($tenantId, $startOfMonth, $endOfMonth),
            'payments' => $this->getPaymentKpis($tenantId, $startOfMonth, $endOfMonth),
            'journal_entries' => $this->getJournalEntryKpis($tenantId, $startOfMonth, $endOfMonth),
            'subscription' => $this->getSubscriptionKpis($tenantId),
            'onboarding' => $this->getOnboardingKpis($tenantId),
        ];
    }

    /**
     * Client KPIs.
     *
     * @return array<string, int>
     */
    private function getClientKpis(int $tenantId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $query = Client::withoutGlobalScopes()->where('tenant_id', $tenantId);

        return [
            'total' => (clone $query)->where('is_active', true)->count(),
            'added_this_month' => (clone $query)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count(),
        ];
    }

    /**
     * Invoice KPIs.
     *
     * @return array<string, mixed>
     */
    private function getInvoiceKpis(int $tenantId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $baseQuery = Invoice::withoutGlobalScopes()->where('tenant_id', $tenantId);

        // Total non-draft invoices
        $total = (clone $baseQuery)
            ->where('status', '!=', InvoiceStatus::Draft)
            ->count();

        // Outstanding: sent or partially_paid
        $outstandingStatuses = [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid];

        $outstandingQuery = (clone $baseQuery)
            ->whereIn('status', $outstandingStatuses);

        $outstandingCount = (clone $outstandingQuery)->count();

        $outstandingAmount = (clone $outstandingQuery)
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');

        // Overdue: sent or partially_paid with due_date in the past
        $overdueQuery = (clone $baseQuery)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid])
            ->where('due_date', '<', today());

        $overdueCount = (clone $overdueQuery)->count();

        $overdueAmount = (clone $overdueQuery)
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');

        // Paid this month
        $paidThisMonth = (clone $baseQuery)
            ->where('status', InvoiceStatus::Paid)
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $revenueThisMonth = (clone $baseQuery)
            ->where('status', InvoiceStatus::Paid)
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        return [
            'total' => $total,
            'outstanding' => $outstandingCount,
            'outstanding_amount' => (float) $outstandingAmount,
            'overdue' => $overdueCount,
            'overdue_amount' => (float) $overdueAmount,
            'paid_this_month' => $paidThisMonth,
            'revenue_this_month' => (float) $revenueThisMonth,
        ];
    }

    /**
     * Payment KPIs.
     *
     * @return array<string, mixed>
     */
    private function getPaymentKpis(int $tenantId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $query = Payment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        return [
            'received_this_month' => (float) (clone $query)->sum('amount'),
            'count_this_month' => (clone $query)->count(),
        ];
    }

    /**
     * Journal Entry KPIs.
     *
     * @return array<string, int>
     */
    private function getJournalEntryKpis(int $tenantId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $baseQuery = JournalEntry::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', JournalEntryStatus::Posted);

        return [
            'total' => (clone $baseQuery)->count(),
            'this_month' => (clone $baseQuery)
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->count(),
        ];
    }

    /**
     * Subscription KPIs.
     *
     * @return array<string, mixed>
     */
    private function getSubscriptionKpis(int $tenantId): array
    {
        $subscription = Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->orderByRaw('CASE
                WHEN status = ? THEN 1
                WHEN status = ? THEN 2
                WHEN status = ? THEN 3
                ELSE 4
            END', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->first();

        if (! $subscription) {
            return [
                'plan_name' => null,
                'status' => null,
                'trial_days_remaining' => null,
            ];
        }

        $trialDaysRemaining = null;

        if ($subscription->status === SubscriptionStatus::Trial && $subscription->trial_ends_at) {
            $trialDaysRemaining = max(0, (int) now()->diffInDays($subscription->trial_ends_at, false));
        }

        return [
            'plan_name' => $subscription->plan?->name_ar,
            'status' => $subscription->status->labelAr(),
            'trial_days_remaining' => $trialDaysRemaining,
        ];
    }

    /**
     * Onboarding KPIs.
     *
     * @return array<string, mixed>
     */
    private function getOnboardingKpis(int $tenantId): array
    {
        $onboarding = $this->onboardingService->getProgress($tenantId);

        return [
            'completed' => (bool) $onboarding->wizard_completed,
            'percent' => $onboarding->completionPercent(),
        ];
    }
}
