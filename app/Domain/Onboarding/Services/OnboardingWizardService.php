<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\NormalBalance;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Services\JournalEntryService;
use App\Domain\Onboarding\Models\CoaTemplate;
use App\Domain\Onboarding\Models\OnboardingProgress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingWizardService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    /**
     * Return current progress with completed steps and next action.
     */
    public function getProgress(int $tenantId): OnboardingProgress
    {
        $progress = OnboardingProgress::withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'current_step' => 1,
                    'total_steps' => 7,
                    'completed_steps' => [],
                ],
            );

        // firstOrCreate leaves columns that relied on DB defaults (e.g. the
        // bool flags, completed_at) absent from the model's attribute array,
        // which the strict preventAccessingMissingAttributes flag then
        // rejects. Refresh to hydrate the full row.
        if ($progress->wasRecentlyCreated) {
            $progress->refresh();
        }

        return $progress;
    }

    /**
     * Load COA template and create accounts for tenant.
     *
     * @return int Number of accounts created.
     *
     * @throws ValidationException
     */
    public function selectCoaTemplate(int $tenantId, string $industry): int
    {
        $template = CoaTemplate::query()
            ->where('industry', $industry)
            ->first();

        if (! $template) {
            throw ValidationException::withMessages([
                'industry' => ["No COA template found for industry: {$industry}"],
            ]);
        }

        // Check if accounts already exist
        $existingAccounts = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($existingAccounts) {
            throw ValidationException::withMessages([
                'industry' => ['Chart of Accounts already exists for this tenant. Cannot overwrite.'],
            ]);
        }

        $accounts = $template->accounts;
        $now = now();

        // Build parent_code -> id mapping as we insert level by level
        $codeToId = [];

        // Sort accounts by depth of parent hierarchy to insert parents first
        $byLevel = $this->groupAccountsByLevel($accounts);

        foreach ($byLevel as $levelAccounts) {
            $rows = [];
            foreach ($levelAccounts as $account) {
                $parentId = null;
                if ($account['parent_code'] !== null) {
                    $parentId = $codeToId[$account['parent_code']] ?? null;
                }

                $type = $account['type'] instanceof AccountType
                    ? $account['type']
                    : AccountType::from($account['type']);

                $normalBalance = $account['normal_balance'] instanceof NormalBalance
                    ? $account['normal_balance']
                    : NormalBalance::from($account['normal_balance']);

                $hasChildren = $this->hasChildren($account['code'], $accounts);

                $rows[] = [
                    'tenant_id' => $tenantId,
                    'parent_id' => $parentId,
                    'code' => $account['code'],
                    'name_ar' => $account['name_ar'],
                    'name_en' => $account['name_en'],
                    'type' => $type->value,
                    'normal_balance' => $normalBalance->value,
                    'is_active' => true,
                    'is_group' => $hasChildren,
                    'level' => $this->getLevel($account['code'], $accounts),
                    'currency' => 'EGP',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 50) as $chunk) {
                Account::query()->withoutGlobalScopes()->insert($chunk);
            }

            // Fetch inserted IDs for parent resolution of next levels
            $codes = array_column($levelAccounts, 'code');
            $inserted = Account::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('code', $codes)
                ->pluck('id', 'code')
                ->toArray();

            $codeToId = array_merge($codeToId, $inserted);
        }

        $count = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        // Mark COA step complete
        $this->completeStep($tenantId, 'coa_selection');

        return $count;
    }

    /**
     * Accept array of {account_code, debit, credit}. Create opening balance journal entry.
     * Validate debits = credits (bcmath). Return the JE.
     *
     * @param  array<int, array{account_code: string, debit: string|float, credit: string|float}>  $balances
     *
     * @throws ValidationException
     */
    public function importOpeningBalances(int $tenantId, array $balances): JournalEntry
    {
        // Validate debits = credits using bcmath
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($balances as $balance) {
            $totalDebit = bcadd($totalDebit, (string) ($balance['debit'] ?? '0'), 2);
            $totalCredit = bcadd($totalCredit, (string) ($balance['credit'] ?? '0'), 2);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'balances' => ["Total debits ({$totalDebit}) must equal total credits ({$totalCredit})."],
            ]);
        }

        if (bccomp($totalDebit, '0.00', 2) === 0) {
            throw ValidationException::withMessages([
                'balances' => ['Opening balances cannot all be zero.'],
            ]);
        }

        // Resolve account codes to IDs
        $codes = array_column($balances, 'account_code');
        $accountMap = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('code', $codes)
            ->pluck('id', 'code')
            ->toArray();

        $lines = [];
        foreach ($balances as $balance) {
            $accountId = $accountMap[$balance['account_code']] ?? null;
            if (! $accountId) {
                throw ValidationException::withMessages([
                    'balances' => ["Account code '{$balance['account_code']}' not found for this tenant."],
                ]);
            }

            $debit = (float) ($balance['debit'] ?? 0);
            $credit = (float) ($balance['credit'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $lines[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => 'قيد الأرصدة الافتتاحية',
            ];
        }

        $journalEntry = $this->journalEntryService->create([
            'date' => now()->startOfYear()->toDateString(),
            'description' => 'أرصدة افتتاحية - Opening Balances',
            'reference' => 'OB-'.now()->format('Y'),
            'lines' => $lines,
        ]);

        // Post the journal entry
        $this->journalEntryService->post($journalEntry);

        // Mark step complete
        $this->completeStep($tenantId, 'opening_balances');

        return $journalEntry;
    }

    /**
     * Mark a step as completed. Advance current_step. If all done, set completed_at.
     *
     * @throws ValidationException
     */
    public function completeStep(int $tenantId, string $step): OnboardingProgress
    {
        $validSteps = OnboardingProgress::STEPS;
        if (! in_array($step, $validSteps, true)) {
            throw ValidationException::withMessages([
                'step' => ["Invalid onboarding step: {$step}. Valid steps: ".implode(', ', $validSteps)],
            ]);
        }

        $progress = $this->getProgress($tenantId);

        // Mark column if it exists
        $column = OnboardingProgress::STEP_COLUMNS[$step] ?? null;
        if ($column) {
            $progress->{$column} = true;
        }

        // Add to completed_steps if not already there
        $completedSteps = is_array($progress->completed_steps) ? $progress->completed_steps : [];
        if (! in_array($step, $completedSteps, true)) {
            $completedSteps[] = $step;
            $progress->completed_steps = $completedSteps;
        }

        // Advance current_step
        $stepIndex = array_search($step, $validSteps, true);
        $nextStep = min((int) $stepIndex + 2, count($validSteps));
        if ($nextStep > $progress->current_step) {
            $progress->current_step = $nextStep;
        }

        // Check if all steps with columns are done
        $allComplete = true;
        foreach (OnboardingProgress::STEP_COLUMNS as $col) {
            if (! $progress->{$col}) {
                $allComplete = false;
                break;
            }
        }

        if ($allComplete && $progress->completed_at === null) {
            $progress->completed_at = now();
        }

        $progress->save();

        return $progress->refresh();
    }

    /**
     * List available COA templates.
     *
     * @return Collection<int, CoaTemplate>
     */
    public function getTemplates(): Collection
    {
        return CoaTemplate::query()
            ->select(['id', 'name_ar', 'name_en', 'industry', 'is_default'])
            ->orderByDesc('is_default')
            ->orderBy('name_en')
            ->get();
    }

    /**
     * Mark step as skipped (still advances progress).
     *
     * @throws ValidationException
     */
    public function skipStep(int $tenantId, string $step): OnboardingProgress
    {
        $validSteps = OnboardingProgress::STEPS;
        if (! in_array($step, $validSteps, true)) {
            throw ValidationException::withMessages([
                'step' => ["Invalid onboarding step: {$step}."],
            ]);
        }

        $progress = $this->getProgress($tenantId);

        // Add to completed_steps as skipped
        $completedSteps = is_array($progress->completed_steps) ? $progress->completed_steps : [];
        $skippedKey = "{$step}:skipped";
        if (! in_array($step, $completedSteps, true) && ! in_array($skippedKey, $completedSteps, true)) {
            $completedSteps[] = $skippedKey;
            $progress->completed_steps = $completedSteps;
        }

        // Advance current_step
        $stepIndex = array_search($step, $validSteps, true);
        $nextStep = min((int) $stepIndex + 2, count($validSteps));
        if ($nextStep > $progress->current_step) {
            $progress->current_step = $nextStep;
        }

        $progress->save();

        return $progress->refresh();
    }

    // ──────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────

    /**
     * Group template accounts by their depth level in the hierarchy.
     *
     * @param  array<int, array<string, mixed>>  $accounts
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupAccountsByLevel(array $accounts): array
    {
        $byLevel = [];
        foreach ($accounts as $account) {
            $level = $this->getLevel($account['code'], $accounts);
            $byLevel[$level][] = $account;
        }

        ksort($byLevel);

        return $byLevel;
    }

    /**
     * Determine the nesting level of an account based on its parent chain.
     *
     * @param  array<int, array<string, mixed>>  $allAccounts
     */
    private function getLevel(string $code, array $allAccounts): int
    {
        $level = 1;
        $current = null;

        foreach ($allAccounts as $account) {
            if ($account['code'] === $code) {
                $current = $account;
                break;
            }
        }

        if (! $current || $current['parent_code'] === null) {
            return $level;
        }

        return 1 + $this->getLevel($current['parent_code'], $allAccounts);
    }

    /**
     * Check if a given account code has children in the template.
     *
     * @param  array<int, array<string, mixed>>  $allAccounts
     */
    private function hasChildren(string $code, array $allAccounts): bool
    {
        foreach ($allAccounts as $account) {
            if ($account['parent_code'] === $code) {
                return true;
            }
        }

        return false;
    }
}
