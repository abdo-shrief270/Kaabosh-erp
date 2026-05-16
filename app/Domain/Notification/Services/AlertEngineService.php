<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\AlertHistory;
use App\Domain\Notification\Models\AlertRule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AlertEngineService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ──────────────────────────────────────
    // CRUD
    // ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return AlertRule::query()
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['metric']), fn ($q) => $q->where('metric', $filters['metric']))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AlertRule
    {
        return AlertRule::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AlertRule $rule, array $data): AlertRule
    {
        $rule->update($data);

        return $rule->refresh();
    }

    public function delete(AlertRule $rule): void
    {
        $rule->delete();
    }

    public function toggle(AlertRule $rule): AlertRule
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        return $rule->refresh();
    }

    // ──────────────────────────────────────
    // Evaluation
    // ──────────────────────────────────────

    /**
     * Evaluate a single alert rule. Returns the AlertHistory if triggered, null otherwise.
     */
    public function evaluate(AlertRule $rule): ?AlertHistory
    {
        if (! $rule->is_active) {
            return null;
        }

        if ($rule->isInCooldown()) {
            return null;
        }

        $metricValue = $this->getMetricValue($rule->metric, $rule->tenant_id);

        if (! $rule->conditionMet($metricValue)) {
            return null;
        }

        return $this->trigger($rule, $metricValue);
    }

    /**
     * Evaluate all active rules for a tenant.
     *
     * @return array<AlertHistory>
     */
    public function evaluateAll(int $tenantId): array
    {
        $rules = AlertRule::query()
            ->forTenant($tenantId)
            ->active()
            ->get();

        $triggered = [];

        foreach ($rules as $rule) {
            $history = $this->evaluate($rule);

            if ($history !== null) {
                $triggered[] = $history;
            }
        }

        return $triggered;
    }

    // ──────────────────────────────────────
    // Metric Calculations (bcmath)
    // ──────────────────────────────────────

    /**
     * Calculate the current value of a metric for a given tenant.
     */
    public function getMetricValue(string $metric, int $tenantId): string
    {
        return match ($metric) {
            'dso' => $this->calculateDso($tenantId),
            'ar_total' => $this->calculateArTotal($tenantId),
            'ap_total' => $this->calculateApTotal($tenantId),
            'cash_balance' => $this->calculateCashBalance($tenantId),
            'overdue_invoices_count' => $this->calculateOverdueInvoicesCount($tenantId),
            'overdue_bills_count' => $this->calculateOverdueBillsCount($tenantId),
            'vat_due_date' => '0.00', // placeholder — days until next VAT due
            'budget_utilization' => $this->calculateBudgetUtilization($tenantId),
            'collection_rate' => $this->calculateCollectionRate($tenantId),
            default => '0.00',
        };
    }

    /**
     * Days Sales Outstanding = (AR Total / Credit Sales in Period) * Period Days.
     * Simplified: AR / (Total Invoiced in last 90 days) * 90.
     */
    private function calculateDso(int $tenantId): string
    {
        $arTotal = $this->calculateArTotal($tenantId);
        $periodDays = '90';

        $creditSales = (string) DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->where('issue_date', '>=', now()->subDays(90))
            ->sum('total');

        if (bccomp($creditSales, '0', 2) === 0) {
            return '0.00';
        }

        // DSO = (AR * 90) / Credit Sales — multiply first to avoid bcmath
        // truncation error (dividing first at scale 6 would drop 30.00 to
        // 29.99 for perfectly round inputs).
        return bcdiv(
            bcmul($arTotal, $periodDays, 2),
            $creditSales,
            2
        );
    }

    /**
     * Sum of outstanding (unpaid) invoice balances.
     */
    private function calculateArTotal(int $tenantId): string
    {
        $total = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->sum(DB::raw('total - COALESCE(amount_paid, 0)'));

        return bcadd((string) ($total ?? 0), '0', 2);
    }

    /**
     * Sum of outstanding bill balances.
     */
    private function calculateApTotal(int $tenantId): string
    {
        $total = DB::table('bills')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'overdue', 'partially_paid'])
            ->sum(DB::raw('total - COALESCE(amount_paid, 0)'));

        return bcadd((string) ($total ?? 0), '0', 2);
    }

    /**
     * Sum of balances for cash and bank GL accounts.
     */
    private function calculateCashBalance(int $tenantId): string
    {
        // Cash & bank accounts typically have codes starting with 1 (assets)
        // and type 'cash' or 'bank'. We sum their journal entry balances.
        $balance = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->whereIn('a.type', ['cash', 'bank'])
            ->selectRaw('COALESCE(SUM(jel.debit - jel.credit), 0) as balance')
            ->value('balance');

        return bcadd((string) ($balance ?? 0), '0', 2);
    }

    /**
     * Count of invoices past their due date.
     */
    private function calculateOverdueInvoicesCount(int $tenantId): string
    {
        $count = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return (string) $count;
    }

    /**
     * Count of bills past their due date.
     */
    private function calculateOverdueBillsCount(int $tenantId): string
    {
        $count = DB::table('bills')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'overdue', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return (string) $count;
    }

    /**
     * Budget utilization as a percentage: (actual spend / budget total) * 100.
     */
    private function calculateBudgetUtilization(int $tenantId): string
    {
        $budget = DB::table('budgets')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->first();

        if (! $budget) {
            return '0.00';
        }

        $totalBudgeted = (string) DB::table('budget_lines')
            ->where('budget_id', $budget->id)
            ->sum('annual_amount');

        if (bccomp($totalBudgeted, '0', 2) === 0) {
            return '0.00';
        }

        // Actual spend: sum of debits on expense accounts in the budget's fiscal year
        $actualSpend = (string) DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->join('budget_lines as bl', 'bl.account_id', '=', 'a.id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', 'posted')
            ->where('bl.budget_id', $budget->id)
            ->sum('jel.debit');

        // utilization = (actual / budget) * 100
        $ratio = bcdiv($actualSpend, $totalBudgeted, 6);

        return bcmul($ratio, '100', 2);
    }

    /**
     * Collection rate = (payments received / total invoiced) * 100.
     */
    private function calculateCollectionRate(int $tenantId): string
    {
        $totalInvoiced = (string) DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        if (bccomp($totalInvoiced, '0', 2) === 0) {
            return '0.00';
        }

        $totalPaid = (string) DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->sum('amount');

        $ratio = bcdiv($totalPaid, $totalInvoiced, 6);

        return bcmul($ratio, '100', 2);
    }

    // ──────────────────────────────────────
    // Alert History
    // ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     */
    public function history(array $filters = []): LengthAwarePaginator
    {
        return AlertHistory::query()
            ->with('alertRule:id,name_ar,name_en,metric')
            ->when(isset($filters['alert_rule_id']), fn ($q) => $q->where('alert_rule_id', $filters['alert_rule_id']))
            ->orderByDesc('triggered_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    // ──────────────────────────────────────
    // Private — Trigger
    // ──────────────────────────────────────

    private function trigger(AlertRule $rule, string $metricValue): AlertHistory
    {
        $operatorLabels = [
            'gt' => 'أكبر من',
            'gte' => 'أكبر من أو يساوي',
            'lt' => 'أقل من',
            'lte' => 'أقل من أو يساوي',
            'eq' => 'يساوي',
        ];

        $operatorLabelsEn = [
            'gt' => 'greater than',
            'gte' => 'greater than or equal to',
            'lt' => 'less than',
            'lte' => 'less than or equal to',
            'eq' => 'equal to',
        ];

        $messageAr = "تنبيه: {$rule->name_ar} — القيمة الحالية ({$metricValue}) "
            .($operatorLabels[$rule->operator] ?? $rule->operator)
            ." الحد ({$rule->threshold})";

        $messageEn = 'Alert: '.($rule->name_en ?? $rule->name_ar)." — current value ({$metricValue}) is "
            .($operatorLabelsEn[$rule->operator] ?? $rule->operator)
            ." threshold ({$rule->threshold})";

        // Resolve recipient user IDs
        $recipientIds = $this->resolveRecipients($rule);

        $history = AlertHistory::query()->create([
            'tenant_id' => $rule->tenant_id,
            'alert_rule_id' => $rule->id,
            'triggered_at' => now(),
            'metric_value' => $metricValue,
            'threshold_value' => (string) $rule->threshold,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'notified_users' => $recipientIds,
        ]);

        // Update rule trigger metadata
        $rule->update([
            'last_triggered_at' => now(),
            'trigger_count' => $rule->trigger_count + 1,
        ]);

        // Dispatch notifications to each recipient
        foreach ($recipientIds as $userId) {
            $this->notificationService->send(
                userId: (int) $userId,
                type: NotificationType::SystemAlert,
                titleAr: $messageAr,
                titleEn: $messageEn,
                bodyAr: $messageAr,
                bodyEn: $messageEn,
                actionUrl: '/alert-rules',
                data: [
                    'alert_rule_id' => $rule->id,
                    'metric' => $rule->metric,
                    'metric_value' => $metricValue,
                    'threshold' => (string) $rule->threshold,
                ],
                channel: NotificationChannel::InApp,
            );
        }

        return $history;
    }

    /**
     * Resolve recipient user IDs from the rule's recipients config.
     *
     * @return array<int>
     */
    private function resolveRecipients(AlertRule $rule): array
    {
        $recipients = $rule->recipients ?? [];

        if (in_array('all_admins', $recipients, true)) {
            return User::query()
                ->where('tenant_id', $rule->tenant_id)
                ->where('role', 'admin')
                ->pluck('id')
                ->all();
        }

        // Filter to only existing user IDs in this tenant
        return User::query()
            ->where('tenant_id', $rule->tenant_id)
            ->whereIn('id', $recipients)
            ->pluck('id')
            ->all();
    }
}
