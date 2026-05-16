<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Services;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\JournalEntryStatus;
use App\Domain\Accounting\Models\Account;
use App\Domain\AccountsPayable\Enums\BillStatus;
use App\Domain\Billing\Enums\InvoiceStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardService
{
    // ──────────────────────────────────────
    // 1. Financial Overview
    // ──────────────────────────────────────

    /**
     * @return array{
     *     revenue_ytd: string,
     *     expenses_ytd: string,
     *     net_profit_ytd: string,
     *     cash_balance: string,
     *     ar_outstanding: string,
     *     ap_outstanding: string,
     *     revenue_vs_last_year: array{current: string, previous: string, change: string, change_percent: string},
     *     expense_vs_budget: array{actual: string, budget: string, variance: string, variance_percent: string},
     * }
     */
    public function financialOverview(array $filters): array
    {
        $tenantId = app('tenant.id');
        $cacheKey = "dashboard:{$tenantId}:financial_overview:".md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $toDate = $filters['to'] ?? date('Y-m-d');
            $year = substr($toDate, 0, 4);
            $fromDate = $filters['from'] ?? "{$year}-01-01";

            // Revenue & Expenses YTD from GL
            $revenueYtd = $this->sumByAccountType(AccountType::Revenue, $fromDate, $toDate);
            $expensesYtd = $this->sumByAccountType(AccountType::Expense, $fromDate, $toDate);
            $netProfitYtd = bcsub($revenueYtd, $expensesYtd, 2);

            // Cash balance: sum of cash + bank accounts
            $cashBalance = $this->getCashBalance($toDate);

            // AR outstanding (invoices not fully paid)
            $arOutstanding = $this->getArOutstanding();

            // AP outstanding (bills not fully paid)
            $apOutstanding = $this->getApOutstanding();

            // Revenue vs last year
            $prevYear = (string) ((int) $year - 1);
            $prevFromDate = "{$prevYear}-01-01";
            $prevToDate = $prevYear.substr($toDate, 4);
            $prevRevenue = $this->sumByAccountType(AccountType::Revenue, $prevFromDate, $prevToDate);
            $revenueChange = bcsub($revenueYtd, $prevRevenue, 2);
            $revenueChangePercent = bccomp($prevRevenue, '0', 2) !== 0
                ? bcmul(bcdiv($revenueChange, $prevRevenue, 4), '100', 2)
                : '0.00';

            // Expense vs budget
            $budgetAmount = $this->getTotalBudget($fromDate, $toDate);
            $budgetVariance = bcsub($budgetAmount, $expensesYtd, 2);
            $budgetVariancePercent = bccomp($budgetAmount, '0', 2) !== 0
                ? bcmul(bcdiv($budgetVariance, $budgetAmount, 4), '100', 2)
                : '0.00';

            return [
                'revenue_ytd' => $revenueYtd,
                'expenses_ytd' => $expensesYtd,
                'net_profit_ytd' => $netProfitYtd,
                'cash_balance' => $cashBalance,
                'ar_outstanding' => $arOutstanding,
                'ap_outstanding' => $apOutstanding,
                'revenue_vs_last_year' => [
                    'current' => $revenueYtd,
                    'previous' => $prevRevenue,
                    'change' => $revenueChange,
                    'change_percent' => $revenueChangePercent,
                ],
                'expense_vs_budget' => [
                    'actual' => $expensesYtd,
                    'budget' => $budgetAmount,
                    'variance' => $budgetVariance,
                    'variance_percent' => $budgetVariancePercent,
                ],
            ];
        });
    }

    // ──────────────────────────────────────
    // 2. Revenue Analysis
    // ──────────────────────────────────────

    /**
     * @return array{
     *     by_month: array<int, array{month: string, revenue: string}>,
     *     by_client: array<int, array{client_id: int, client_name: string, revenue: string}>,
     *     by_account: array<int, array{account_id: int, account_code: string, account_name: string, revenue: string}>,
     *     growth_rates: array<int, array{month: string, revenue: string, growth_rate: string}>,
     * }
     */
    public function revenueAnalysis(array $filters): array
    {
        $tenantId = app('tenant.id');
        $cacheKey = "dashboard:{$tenantId}:revenue_analysis:".md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $toDate = $filters['to'] ?? date('Y-m-d');
            $fromDate = $filters['from'] ?? date('Y-m-d', strtotime('-12 months', strtotime($toDate)));

            return [
                'by_month' => $this->revenueByMonth($fromDate, $toDate),
                'by_client' => $this->revenueByClient($fromDate, $toDate),
                'by_account' => $this->revenueByAccount($fromDate, $toDate),
                'growth_rates' => $this->revenueGrowthRates($fromDate, $toDate),
            ];
        });
    }

    // ──────────────────────────────────────
    // 3. Cash Flow Forecast
    // ──────────────────────────────────────

    /**
     * @return array{
     *     current_cash_position: string,
     *     expected_inflows: string,
     *     expected_outflows: string,
     *     projected_30_days: string,
     *     projected_60_days: string,
     *     projected_90_days: string,
     * }
     */
    public function cashFlowForecast(array $filters): array
    {
        $tenantId = app('tenant.id');
        $cacheKey = "dashboard:{$tenantId}:cash_flow_forecast:".md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $asOfDate = $filters['to'] ?? date('Y-m-d');

            $currentCash = $this->getCashBalance($asOfDate);

            // Expected inflows from AR aging weighted by collection probability
            $arAging = $this->getArAging($asOfDate);
            $expectedInflows = $this->calculateWeightedInflows($arAging);

            // Expected outflows from AP aging
            $expectedOutflows = $this->getExpectedOutflows($asOfDate);

            // Project cash position for 30/60/90 days
            $projected30 = bcadd($currentCash, bcsub($expectedInflows['30_days'], $expectedOutflows['30_days'], 2), 2);
            $projected60 = bcadd($currentCash, bcsub($expectedInflows['60_days'], $expectedOutflows['60_days'], 2), 2);
            $projected90 = bcadd($currentCash, bcsub($expectedInflows['90_days'], $expectedOutflows['90_days'], 2), 2);

            return [
                'current_cash_position' => $currentCash,
                'expected_inflows' => $expectedInflows['total'],
                'expected_outflows' => $expectedOutflows['total'],
                'projected_30_days' => $projected30,
                'projected_60_days' => $projected60,
                'projected_90_days' => $projected90,
            ];
        });
    }

    // ──────────────────────────────────────
    // 4. Profitability Metrics
    // ──────────────────────────────────────

    /**
     * @return array{
     *     gross_margin_percent: string,
     *     net_margin_percent: string,
     *     operating_expense_ratio: string,
     *     revenue_per_client: string,
     *     top_profitable_clients: array,
     * }
     */
    public function profitabilityMetrics(array $filters): array
    {
        $tenantId = app('tenant.id');
        $cacheKey = "dashboard:{$tenantId}:profitability_metrics:".md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $toDate = $filters['to'] ?? date('Y-m-d');
            $year = substr($toDate, 0, 4);
            $fromDate = $filters['from'] ?? "{$year}-01-01";

            $revenue = $this->sumByAccountType(AccountType::Revenue, $fromDate, $toDate);
            $expenses = $this->sumByAccountType(AccountType::Expense, $fromDate, $toDate);

            // COGS: expense accounts starting with '51' (Cost of Goods Sold / Cost of Sales)
            $cogs = $this->sumByAccountPrefix('51', $fromDate, $toDate, true);

            $grossProfit = bcsub($revenue, $cogs, 2);
            $grossMarginPercent = bccomp($revenue, '0', 2) !== 0
                ? bcmul(bcdiv($grossProfit, $revenue, 4), '100', 2)
                : '0.00';

            $netMarginPercent = bccomp($revenue, '0', 2) !== 0
                ? bcmul(bcdiv(bcsub($revenue, $expenses, 2), $revenue, 4), '100', 2)
                : '0.00';

            $operatingExpenseRatio = bccomp($revenue, '0', 2) !== 0
                ? bcmul(bcdiv($expenses, $revenue, 4), '100', 2)
                : '0.00';

            // Revenue per client (average)
            $activeClients = $this->getActiveClientCount();
            $revenuePerClient = $activeClients > 0
                ? bcdiv($revenue, (string) $activeClients, 2)
                : '0.00';

            // Top 5 most profitable clients
            $topClients = $this->getTopProfitableClients($fromDate, $toDate, 5);

            return [
                'gross_margin_percent' => $grossMarginPercent,
                'net_margin_percent' => $netMarginPercent,
                'operating_expense_ratio' => $operatingExpenseRatio,
                'revenue_per_client' => $revenuePerClient,
                'top_profitable_clients' => $topClients,
            ];
        });
    }

    // ──────────────────────────────────────
    // 5. KPI Dashboard
    // ──────────────────────────────────────

    /**
     * @return array{
     *     dso: string,
     *     dpo: string,
     *     current_ratio: string,
     *     quick_ratio: string,
     *     collection_rate: string,
     * }
     */
    public function kpiDashboard(array $filters): array
    {
        $tenantId = app('tenant.id');
        $cacheKey = "dashboard:{$tenantId}:kpi_dashboard:".md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $toDate = $filters['to'] ?? date('Y-m-d');
            $year = substr($toDate, 0, 4);
            $fromDate = $filters['from'] ?? "{$year}-01-01";

            // Inclusive day count: from 2026-01-01 to 2026-03-31 is 90 days.
            $periodDays = max(1, ((int) ((strtotime($toDate) - strtotime($fromDate)) / 86400)) + 1);

            // DSO = (AR * Period Days) / Credit Sales
            // Multiply first so bcmath scale-2 truncation doesn't eat a penny on
            // clean inputs (e.g. 100000/300000 * 90 = 30.00, not 29.99).
            $arBalance = $this->getAccountTypeBalance(AccountType::Asset, '1121', $toDate);
            $creditSales = $this->sumByAccountType(AccountType::Revenue, $fromDate, $toDate);
            $dso = bccomp($creditSales, '0', 2) !== 0
                ? bcdiv(bcmul($arBalance, (string) $periodDays, 2), $creditSales, 2)
                : '0.00';

            // DPO = (AP * Period Days) / Cost of Sales (same ordering as DSO).
            $apBalance = $this->getAccountTypeBalance(AccountType::Liability, '2111', $toDate);
            $costOfSales = $this->sumByAccountPrefix('51', $fromDate, $toDate, true);
            $dpo = bccomp($costOfSales, '0', 2) !== 0
                ? bcdiv(bcmul($apBalance, (string) $periodDays, 2), $costOfSales, 2)
                : '0.00';

            // Current ratio = Current Assets / Current Liabilities
            $currentAssets = $this->sumCurrentAssets($toDate);
            $currentLiabilities = $this->sumCurrentLiabilities($toDate);
            $currentRatio = bccomp($currentLiabilities, '0', 2) !== 0
                ? bcdiv($currentAssets, $currentLiabilities, 2)
                : '0.00';

            // Quick ratio = (Current Assets - Inventory) / Current Liabilities
            $inventory = $this->getInventoryBalance($toDate);
            $quickAssets = bcsub($currentAssets, $inventory, 2);
            $quickRatio = bccomp($currentLiabilities, '0', 2) !== 0
                ? bcdiv($quickAssets, $currentLiabilities, 2)
                : '0.00';

            // Collection rate = (Payments Received / Total Invoiced) * 100
            $totalInvoiced = $this->getTotalInvoiced($fromDate, $toDate);
            $paymentsReceived = $this->getPaymentsReceived($fromDate, $toDate);
            $collectionRate = bccomp($totalInvoiced, '0', 2) !== 0
                ? bcmul(bcdiv($paymentsReceived, $totalInvoiced, 4), '100', 2)
                : '0.00';

            return [
                'dso' => $dso,
                'dpo' => $dpo,
                'current_ratio' => $currentRatio,
                'quick_ratio' => $quickRatio,
                'collection_rate' => $collectionRate,
            ];
        });
    }

    // ──────────────────────────────────────
    // 6. Comparison Report
    // ──────────────────────────────────────

    /**
     * Side-by-side P&L for two periods with variance.
     *
     * @return array{
     *     period_a: array{from: string, to: string, revenue: string, expenses: string, net_income: string},
     *     period_b: array{from: string, to: string, revenue: string, expenses: string, net_income: string},
     *     variance: array{revenue: array, expenses: array, net_income: array},
     * }
     */
    public function comparisonReport(string $periodA, string $periodB): array
    {
        // Periods are expected as "YYYY-MM-DD:YYYY-MM-DD"
        [$fromA, $toA] = explode(':', $periodA);
        [$fromB, $toB] = explode(':', $periodB);

        $revenueA = $this->sumByAccountType(AccountType::Revenue, $fromA, $toA);
        $expensesA = $this->sumByAccountType(AccountType::Expense, $fromA, $toA);
        $netIncomeA = bcsub($revenueA, $expensesA, 2);

        $revenueB = $this->sumByAccountType(AccountType::Revenue, $fromB, $toB);
        $expensesB = $this->sumByAccountType(AccountType::Expense, $fromB, $toB);
        $netIncomeB = bcsub($revenueB, $expensesB, 2);

        return [
            'period_a' => [
                'from' => $fromA,
                'to' => $toA,
                'revenue' => $revenueA,
                'expenses' => $expensesA,
                'net_income' => $netIncomeA,
            ],
            'period_b' => [
                'from' => $fromB,
                'to' => $toB,
                'revenue' => $revenueB,
                'expenses' => $expensesB,
                'net_income' => $netIncomeB,
            ],
            'variance' => [
                'revenue' => $this->calculateVariance($revenueA, $revenueB),
                'expenses' => $this->calculateVariance($expensesA, $expensesB),
                'net_income' => $this->calculateVariance($netIncomeA, $netIncomeB),
            ],
        ];
    }

    // ──────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────

    /**
     * Sum all posted GL amounts for a given account type within a date range.
     * Revenue = credit - debit, Expense = debit - credit.
     */
    private function sumByAccountType(AccountType $type, string $fromDate, string $toDate): string
    {
        $isDebitNormal = in_array($type, [AccountType::Asset, AccountType::Expense], true);

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->where('accounts.type', $type->value)
            ->where('journal_entries.date', '>=', $fromDate)
            ->where('journal_entries.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        return $isDebitNormal
            ? bcsub((string) $result->total_debit, (string) $result->total_credit, 2)
            : bcsub((string) $result->total_credit, (string) $result->total_debit, 2);
    }

    /**
     * Sum GL amounts for accounts whose code starts with a given prefix.
     */
    private function sumByAccountPrefix(string $prefix, string $fromDate, string $toDate, bool $isDebitNormal): string
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->where('accounts.code', 'like', $prefix.'%')
            ->where('journal_entries.date', '>=', $fromDate)
            ->where('journal_entries.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        return $isDebitNormal
            ? bcsub((string) $result->total_debit, (string) $result->total_credit, 2)
            : bcsub((string) $result->total_credit, (string) $result->total_debit, 2);
    }

    /**
     * Get cumulative cash balance (cash + bank accounts) up to a date.
     */
    private function getCashBalance(string $asOfDate): string
    {
        $cashCode = config('accounting.default_accounts.cash');
        $bankCode = config('accounting.default_accounts.bank');

        $accountIds = Account::query()
            ->where(function ($q) use ($cashCode, $bankCode) {
                $q->where('code', $cashCode)->orWhere('code', $bankCode);
            })
            ->when(app('tenant.id'), fn ($q) => $q->where('tenant_id', app('tenant.id')))
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return '0.00';
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.date', '<=', $asOfDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        // Cash is an asset (debit-normal)
        return bcsub((string) $result->total_debit, (string) $result->total_credit, 2);
    }

    /**
     * Get AR outstanding from invoices table.
     */
    private function getArOutstanding(): string
    {
        $query = DB::table('invoices')
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->whereNull('deleted_at');

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
        )->first();

        return number_format((float) ($result->outstanding ?? 0), 2, '.', '');
    }

    /**
     * Get AP outstanding from bills table.
     */
    private function getApOutstanding(): string
    {
        $query = DB::table('bills')
            ->whereIn('status', [
                BillStatus::Approved->value,
                BillStatus::PartiallyPaid->value,
            ])
            ->whereNull('deleted_at');

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
        )->first();

        return number_format((float) ($result->outstanding ?? 0), 2, '.', '');
    }

    /**
     * Get total budget amount for the period.
     */
    private function getTotalBudget(string $fromDate, string $toDate): string
    {
        $year = substr($fromDate, 0, 4);

        $exists = DB::getSchemaBuilder()->hasTable('budgets');
        if (! $exists) {
            return '0.00';
        }

        // budgets.fiscal_year_id FKs to fiscal_years, and fiscal_years only
        // exposes start_date/end_date (no denormalised integer year). Match
        // the year by extracting it from start_date. Schema stores the line
        // total on `annual_amount` (plus m1-m12 monthly breakdown columns)
        // — there is no `amount` column.
        $query = DB::table('budget_lines')
            ->join('budgets', 'budget_lines.budget_id', '=', 'budgets.id')
            ->join('fiscal_years', 'budgets.fiscal_year_id', '=', 'fiscal_years.id')
            ->whereRaw('EXTRACT(YEAR FROM fiscal_years.start_date) = ?', [$year])
            ->where('budgets.status', 'approved');

        if (app('tenant.id')) {
            $query->where('budgets.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(budget_lines.annual_amount), 0) as total')
        )->first();

        return number_format((float) ($result->total ?? 0), 2, '.', '');
    }

    /**
     * Revenue by month (12-month trend).
     */
    private function revenueByMonth(string $fromDate, string $toDate): array
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->where('accounts.type', AccountType::Revenue->value)
            ->where('journal_entries.date', '>=', $fromDate)
            ->where('journal_entries.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        // Postgres doesn't have DATE_FORMAT — use TO_CHAR with the matching
        // format pattern. '%Y-%m' -> 'YYYY-MM'.
        $rows = $query->select(
            DB::raw("TO_CHAR(journal_entries.date, 'YYYY-MM') as month"),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month,
            'revenue' => bcsub((string) $row->total_credit, (string) $row->total_debit, 2),
        ])->values()->toArray();
    }

    /**
     * Revenue by client (top 10).
     */
    private function revenueByClient(string $fromDate, string $toDate): array
    {
        $query = DB::table('invoices')
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->whereNull('invoices.deleted_at')
            ->where('invoices.date', '>=', $fromDate)
            ->where('invoices.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('invoices.tenant_id', app('tenant.id'));
        }

        return $query->select(
            'clients.id as client_id',
            'clients.name as client_name',
            DB::raw('COALESCE(SUM(invoices.total), 0) as revenue')
        )
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'client_id' => $row->client_id,
                'client_name' => $row->client_name,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
            ])
            ->toArray();
    }

    /**
     * Revenue by account/service.
     */
    private function revenueByAccount(string $fromDate, string $toDate): array
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->where('accounts.type', AccountType::Revenue->value)
            ->where('journal_entries.date', '>=', $fromDate)
            ->where('journal_entries.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        return $query->select(
            'accounts.id as account_id',
            'accounts.code as account_code',
            'accounts.name_en as account_name',
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
        )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name_en')
            ->orderByDesc(DB::raw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit)'))
            ->get()
            ->map(fn ($row) => [
                'account_id' => $row->account_id,
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'revenue' => bcsub((string) $row->total_credit, (string) $row->total_debit, 2),
            ])
            ->toArray();
    }

    /**
     * Revenue growth rates month-over-month.
     */
    private function revenueGrowthRates(string $fromDate, string $toDate): array
    {
        $byMonth = $this->revenueByMonth($fromDate, $toDate);
        $rates = [];
        $previous = null;

        foreach ($byMonth as $entry) {
            $growthRate = '0.00';
            if ($previous !== null && bccomp($previous, '0', 2) !== 0) {
                $change = bcsub($entry['revenue'], $previous, 2);
                $growthRate = bcmul(bcdiv($change, $previous, 4), '100', 2);
            }

            $rates[] = [
                'month' => $entry['month'],
                'revenue' => $entry['revenue'],
                'growth_rate' => $growthRate,
            ];

            $previous = $entry['revenue'];
        }

        return $rates;
    }

    /**
     * Get AR aging buckets for forecast.
     */
    private function getArAging(string $asOfDate): array
    {
        $query = DB::table('invoices')
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->whereNull('deleted_at');

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $invoices = $query->select('due_date', DB::raw('(total - amount_paid) as outstanding'))->get();

        $buckets = ['current' => '0.00', '30_days' => '0.00', '60_days' => '0.00', '90_plus' => '0.00'];

        foreach ($invoices as $invoice) {
            $daysOverdue = max(0, (int) ((strtotime($asOfDate) - strtotime($invoice->due_date)) / 86400));
            $amount = number_format((float) $invoice->outstanding, 2, '.', '');

            if ($daysOverdue <= 0) {
                $buckets['current'] = bcadd($buckets['current'], $amount, 2);
            } elseif ($daysOverdue <= 30) {
                $buckets['30_days'] = bcadd($buckets['30_days'], $amount, 2);
            } elseif ($daysOverdue <= 60) {
                $buckets['60_days'] = bcadd($buckets['60_days'], $amount, 2);
            } else {
                $buckets['90_plus'] = bcadd($buckets['90_plus'], $amount, 2);
            }
        }

        return $buckets;
    }

    /**
     * Calculate weighted inflows based on collection probability.
     * Current 95%, 30d 80%, 60d 60%, 90d+ 30%.
     */
    private function calculateWeightedInflows(array $arAging): array
    {
        $current = bcmul($arAging['current'], '0.95', 2);
        $thirtyDay = bcmul($arAging['30_days'], '0.80', 2);
        $sixtyDay = bcmul($arAging['60_days'], '0.60', 2);
        $ninetyPlus = bcmul($arAging['90_plus'], '0.30', 2);

        $total = bcadd(bcadd(bcadd($current, $thirtyDay, 2), $sixtyDay, 2), $ninetyPlus, 2);

        // Distribute inflows across time horizons
        $inflow30 = bcadd($current, $thirtyDay, 2);
        $inflow60 = bcadd($inflow30, $sixtyDay, 2);
        $inflow90 = bcadd($inflow60, $ninetyPlus, 2);

        return [
            'total' => $total,
            '30_days' => $inflow30,
            '60_days' => $inflow60,
            '90_days' => $inflow90,
        ];
    }

    /**
     * Get expected outflows from AP aging.
     */
    private function getExpectedOutflows(string $asOfDate): array
    {
        $query = DB::table('bills')
            ->whereIn('status', [
                BillStatus::Approved->value,
                BillStatus::PartiallyPaid->value,
            ])
            ->whereNull('deleted_at');

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $bills = $query->select('due_date', DB::raw('(total - amount_paid) as outstanding'))->get();

        $outflow30 = '0.00';
        $outflow60 = '0.00';
        $outflow90 = '0.00';
        $total = '0.00';

        foreach ($bills as $bill) {
            $daysUntilDue = (int) ((strtotime($bill->due_date) - strtotime($asOfDate)) / 86400);
            $amount = number_format((float) $bill->outstanding, 2, '.', '');
            $total = bcadd($total, $amount, 2);

            if ($daysUntilDue <= 30) {
                $outflow30 = bcadd($outflow30, $amount, 2);
            }
            if ($daysUntilDue <= 60) {
                $outflow60 = bcadd($outflow60, $amount, 2);
            }
            if ($daysUntilDue <= 90) {
                $outflow90 = bcadd($outflow90, $amount, 2);
            }
        }

        return [
            'total' => $total,
            '30_days' => $outflow30,
            '60_days' => $outflow60,
            '90_days' => $outflow90,
        ];
    }

    /**
     * Get balance for a specific account code (cumulative up to date).
     */
    private function getAccountTypeBalance(AccountType $type, string $code, string $asOfDate): string
    {
        $isDebitNormal = in_array($type, [AccountType::Asset, AccountType::Expense], true);

        $accountIds = Account::query()
            ->where('code', $code)
            ->when(app('tenant.id'), fn ($q) => $q->where('tenant_id', app('tenant.id')))
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return '0.00';
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.date', '<=', $asOfDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        return $isDebitNormal
            ? bcsub((string) $result->total_debit, (string) $result->total_credit, 2)
            : bcsub((string) $result->total_credit, (string) $result->total_debit, 2);
    }

    /**
     * Sum current assets (accounts starting with '11' — cash, bank, AR, inventory, prepaid).
     */
    private function sumCurrentAssets(string $asOfDate): string
    {
        return $this->sumBalanceByPrefix('11', $asOfDate, true);
    }

    /**
     * Sum current liabilities (accounts starting with '21' — AP, accrued, tax payable).
     */
    private function sumCurrentLiabilities(string $asOfDate): string
    {
        return $this->sumBalanceByPrefix('21', $asOfDate, false);
    }

    /**
     * Get inventory balance (codes 1131, 1132).
     */
    private function getInventoryBalance(string $asOfDate): string
    {
        $accountIds = Account::query()
            ->where(function ($q) {
                $q->where('code', 'like', '1131%')->orWhere('code', 'like', '1132%');
            })
            ->when(app('tenant.id'), fn ($q) => $q->where('tenant_id', app('tenant.id')))
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return '0.00';
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.date', '<=', $asOfDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        return bcsub((string) $result->total_debit, (string) $result->total_credit, 2);
    }

    /**
     * Sum cumulative balance for accounts matching a code prefix up to a date.
     */
    private function sumBalanceByPrefix(string $prefix, string $asOfDate, bool $isDebitNormal): string
    {
        $accountIds = Account::query()
            ->where('code', 'like', $prefix.'%')
            ->when(app('tenant.id'), fn ($q) => $q->where('tenant_id', app('tenant.id')))
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return '0.00';
        }

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.date', '<=', $asOfDate);

        if (app('tenant.id')) {
            $query->where('journal_entries.tenant_id', app('tenant.id'));
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )->first();

        if (! $result) {
            return '0.00';
        }

        return $isDebitNormal
            ? bcsub((string) $result->total_debit, (string) $result->total_credit, 2)
            : bcsub((string) $result->total_credit, (string) $result->total_debit, 2);
    }

    /**
     * Get total invoiced amount for a period.
     */
    private function getTotalInvoiced(string $fromDate, string $toDate): string
    {
        $query = DB::table('invoices')
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->whereNull('deleted_at')
            ->where('date', '>=', $fromDate)
            ->where('date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $result = $query->select(DB::raw('COALESCE(SUM(total), 0) as total'))->first();

        return number_format((float) ($result->total ?? 0), 2, '.', '');
    }

    /**
     * Get payments received for a period.
     */
    private function getPaymentsReceived(string $fromDate, string $toDate): string
    {
        $query = DB::table('invoices')
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->whereNull('deleted_at')
            ->where('date', '>=', $fromDate)
            ->where('date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        $result = $query->select(DB::raw('COALESCE(SUM(amount_paid), 0) as total'))->first();

        return number_format((float) ($result->total ?? 0), 2, '.', '');
    }

    /**
     * Get active client count.
     */
    private function getActiveClientCount(): int
    {
        $query = DB::table('clients')
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if (app('tenant.id')) {
            $query->where('tenant_id', app('tenant.id'));
        }

        return $query->count();
    }

    /**
     * Get top N most profitable clients (revenue - direct costs).
     */
    private function getTopProfitableClients(string $fromDate, string $toDate, int $limit): array
    {
        $query = DB::table('invoices')
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->whereNull('invoices.deleted_at')
            ->where('invoices.date', '>=', $fromDate)
            ->where('invoices.date', '<=', $toDate);

        if (app('tenant.id')) {
            $query->where('invoices.tenant_id', app('tenant.id'));
        }

        return $query->select(
            'clients.id as client_id',
            'clients.name as client_name',
            DB::raw('COALESCE(SUM(invoices.total), 0) as revenue'),
            DB::raw('COALESCE(SUM(invoices.subtotal - invoices.total + invoices.vat_amount), 0) as direct_costs'),
            DB::raw('COALESCE(SUM(invoices.total), 0) as profit')
        )
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('profit')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'client_id' => $row->client_id,
                'client_name' => $row->client_name,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
                'profit' => number_format((float) $row->profit, 2, '.', ''),
            ])
            ->toArray();
    }

    /**
     * Calculate variance between two amounts.
     */
    private function calculateVariance(string $amountA, string $amountB): array
    {
        $change = bcsub($amountB, $amountA, 2);
        $changePercent = bccomp($amountA, '0', 2) !== 0
            ? bcmul(bcdiv($change, $amountA, 4), '100', 2)
            : '0.00';

        return [
            'amount_a' => $amountA,
            'amount_b' => $amountB,
            'change' => $change,
            'change_percent' => $changePercent,
        ];
    }
}
