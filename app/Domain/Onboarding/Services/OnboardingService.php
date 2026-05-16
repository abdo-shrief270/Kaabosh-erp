<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\FiscalYear;
use App\Domain\Accounting\Services\FiscalPeriodService;
use App\Domain\Accounting\Services\JournalEntryService;
use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Client\Models\Client;
use App\Domain\Notification\Models\OnboardingStep;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\EgyptianCoASeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly FiscalPeriodService $fiscalPeriodService,
        private readonly JournalEntryService $journalEntryService,
    ) {}

    /**
     * Get or create onboarding progress for the tenant.
     * Auto-detects completed steps from existing data.
     */
    public function getProgress(?int $tenantId = null): OnboardingStep
    {
        $tenantId ??= (int) app('tenant.id');

        $step = OnboardingStep::withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId],
                ['current_step' => 1],
            );

        // Auto-detect progress from existing data
        $tenant = Tenant::query()->find($tenantId);

        $companyDetailsCompleted = $tenant
            && $tenant->name
            && $tenant->email
            && $tenant->phone
            && $tenant->tax_id;

        $coaTemplateSelected = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        $firstClientAdded = Client::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        $firstInvoiceCreated = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        $teamInvited = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count() > 1;

        $step->update([
            'company_details_completed' => $companyDetailsCompleted || $step->company_details_completed,
            'coa_template_selected' => $coaTemplateSelected || $step->coa_template_selected,
            'first_client_added' => $firstClientAdded || $step->first_client_added,
            'first_invoice_created' => $firstInvoiceCreated || $step->first_invoice_created,
            'team_invited' => $teamInvited || $step->team_invited,
        ]);

        $step->refresh();

        // Auto-flip the wizard_completed flag when the four counted steps are
        // all done — even if the user got there outside the SPA's
        // /onboarding wizard (e.g. added a client from /clients directly).
        // Without this the dashboard banner stays visible at 100% forever
        // because percent==100 isn't the field that hides the card —
        // `completed` is.
        if (
            ! $step->wizard_completed
            && $step->company_details_completed
            && $step->coa_template_selected
            && $step->first_client_added
            && $step->team_invited
        ) {
            $step->update([
                'wizard_completed' => true,
                'wizard_completed_at' => now(),
            ]);
            $step->refresh();
        }

        return $step;
    }

    /**
     * Mark a specific onboarding step as completed.
     *
     * @throws ValidationException
     */
    public function completeStep(string $step): OnboardingStep
    {
        $stepMap = [
            'company_details' => 'company_details_completed',
            'coa_template' => 'coa_template_selected',
            'first_client' => 'first_client_added',
            'first_invoice' => 'first_invoice_created',
            'team_invited' => 'team_invited',
            'sample_data' => 'sample_data_loaded',
        ];

        if (! isset($stepMap[$step])) {
            throw ValidationException::withMessages([
                'step' => ["Invalid onboarding step: {$step}"],
            ]);
        }

        $onboarding = $this->getProgress();

        $column = $stepMap[$step];
        $onboarding->update([$column => true]);

        // Advance current_step based on completion order
        $this->advanceCurrentStep($onboarding);

        // Check if all key steps are done
        $onboarding->refresh();

        // Mirror the 4-step flow walked by the SPA's /onboarding page so the
        // dashboard banner can hit 100% and disappear once the user is done.
        // Must stay in sync with OnboardingStep::STEPS.
        if (
            $onboarding->company_details_completed
            && $onboarding->coa_template_selected
            && $onboarding->first_client_added
            && $onboarding->team_invited
            && ! $onboarding->wizard_completed
        ) {
            $onboarding->update([
                'wizard_completed' => true,
                'wizard_completed_at' => now(),
            ]);
        }

        return $onboarding->refresh();
    }

    /**
     * Skip the onboarding wizard entirely.
     */
    public function skipWizard(): OnboardingStep
    {
        $onboarding = $this->getProgress();

        $onboarding->update([
            'wizard_skipped' => true,
            'wizard_completed' => true,
            'wizard_completed_at' => now(),
        ]);

        return $onboarding->refresh();
    }

    /**
     * Set up the Chart of Accounts from a template.
     *
     * @throws ValidationException
     */
    public function setupCoaTemplate(string $templateName, ?int $tenantId = null): void
    {
        $tenantId ??= (int) app('tenant.id');

        $validTemplates = ['general', 'trading', 'services'];

        if (! in_array($templateName, $validTemplates, true)) {
            throw ValidationException::withMessages([
                'template_name' => ["Invalid template name: {$templateName}. Valid options: ".implode(', ', $validTemplates)],
            ]);
        }

        // Check if accounts already exist for tenant
        $existingAccounts = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($existingAccounts) {
            throw ValidationException::withMessages([
                'template_name' => ['Chart of Accounts already exists for this tenant. Cannot overwrite.'],
            ]);
        }

        // For MVP, all templates use the Egyptian CoA seeder (general purpose)
        (new EgyptianCoASeeder)->run($tenantId);

        // Mark the step as completed
        $onboarding = OnboardingStep::withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId],
                ['current_step' => 1],
            );

        $onboarding->update([
            'coa_template_selected' => true,
            'coa_template_name' => $templateName,
        ]);
    }

    /**
     * Create a fiscal year for the current calendar year if none exists.
     */
    public function setupFiscalYear(?int $tenantId = null): void
    {
        $tenantId ??= (int) app('tenant.id');

        $currentYear = (int) now()->format('Y');

        $exists = FiscalYear::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereYear('start_date', $currentYear)
            ->exists();

        if ($exists) {
            return;
        }

        $this->fiscalPeriodService->createYear([
            'tenant_id' => $tenantId,
            'name' => "السنة المالية {$currentYear}",
            'start_date' => "{$currentYear}-01-01",
            'end_date' => "{$currentYear}-12-31",
        ]);
    }

    /**
     * Load sample data for the tenant (clients, invoices, journal entries).
     */
    public function loadSampleData(?int $tenantId = null): void
    {
        $tenantId ??= (int) app('tenant.id');

        DB::transaction(function () use ($tenantId): void {
            // 1. Create 5 sample clients
            $clients = $this->createSampleClients($tenantId);

            // 2. Create fiscal year + 12 periods if not already created
            $this->setupFiscalYear($tenantId);

            // 3. Create 3 sample journal entries (posted revenue transactions)
            $this->createSampleJournalEntries($tenantId);

            // 4. Create 2 sample invoices (1 sent, 1 paid with payment)
            $this->createSampleInvoices($tenantId, $clients);

            // 5. Mark sample_data_loaded
            $onboarding = OnboardingStep::withoutGlobalScopes()
                ->firstOrCreate(
                    ['tenant_id' => $tenantId],
                    ['current_step' => 1],
                );

            $onboarding->update(['sample_data_loaded' => true]);
        });
    }

    /**
     * Invite a team member by creating their user account and sending a notification.
     *
     * @throws ValidationException
     */
    public function inviteTeamMember(string $email, string $name, string $role = 'accountant'): User
    {
        $tenantId = (int) app('tenant.id');

        // Validate email not already in use for this tenant
        $existingUser = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->exists();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists in this tenant.'],
            ]);
        }

        $userRole = UserRole::tryFrom($role) ?? UserRole::Accountant;

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'role' => $userRole,
            'locale' => 'ar',
            'is_active' => true,
        ]);

        // Send team invite notification to the new user
        $inviterName = Auth::user()?->name ?? 'مدير الحساب';
        $this->notificationService->sendTeamInvite($user->id, $inviterName);

        // Mark team_invited step
        $onboarding = OnboardingStep::withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId],
                ['current_step' => 1],
            );

        $onboarding->update(['team_invited' => true]);

        return $user;
    }

    // ──────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────

    /**
     * Advance the current_step number based on which steps are completed.
     */
    private function advanceCurrentStep(OnboardingStep $onboarding): void
    {
        $onboarding->refresh();

        $stepOrder = [
            1 => 'company_details_completed',
            2 => 'coa_template_selected',
            3 => 'first_client_added',
            4 => 'first_invoice_created',
            5 => 'team_invited',
            6 => 'sample_data_loaded',
        ];

        $nextStep = 1;

        foreach ($stepOrder as $stepNum => $column) {
            if ($onboarding->{$column}) {
                $nextStep = $stepNum + 1;
            } else {
                break;
            }
        }

        // Cap at max step
        $nextStep = min($nextStep, count($stepOrder) + 1);

        $onboarding->update(['current_step' => $nextStep]);
    }

    /**
     * Create 5 sample Egyptian clients.
     *
     * @return array<int, Client>
     */
    private function createSampleClients(int $tenantId): array
    {
        $sampleClients = [
            [
                'name' => 'شركة النيل للتجارة',
                'trade_name' => 'النيل للتجارة',
                'tax_id' => '100-200-300',
                'city' => 'القاهرة',
                'phone' => '01000000001',
                'email' => 'info@nile-trading.example.com',
                'contact_person' => 'أحمد محمد',
                'is_active' => true,
            ],
            [
                'name' => 'مؤسسة الأهرام للمقاولات',
                'trade_name' => 'الأهرام للمقاولات',
                'tax_id' => '100-200-301',
                'city' => 'الجيزة',
                'phone' => '01000000002',
                'email' => 'info@ahram-construction.example.com',
                'contact_person' => 'محمد علي',
                'is_active' => true,
            ],
            [
                'name' => 'شركة الدلتا للأغذية',
                'trade_name' => 'الدلتا للأغذية',
                'tax_id' => '100-200-302',
                'city' => 'الإسكندرية',
                'phone' => '01000000003',
                'email' => 'info@delta-food.example.com',
                'contact_person' => 'فاطمة حسن',
                'is_active' => true,
            ],
            [
                'name' => 'مكتب الشرق للاستشارات',
                'trade_name' => 'الشرق للاستشارات',
                'tax_id' => '100-200-303',
                'city' => 'القاهرة',
                'phone' => '01000000004',
                'email' => 'info@sharq-consulting.example.com',
                'contact_person' => 'عمر خالد',
                'is_active' => true,
            ],
            [
                'name' => 'شركة السلام للتوريدات',
                'trade_name' => 'السلام للتوريدات',
                'tax_id' => '100-200-304',
                'city' => 'المنصورة',
                'phone' => '01000000005',
                'email' => 'info@salam-supply.example.com',
                'contact_person' => 'سارة أحمد',
                'is_active' => true,
            ],
        ];

        $clients = [];

        foreach ($sampleClients as $clientData) {
            $clients[] = Client::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                ...$clientData,
            ]);
        }

        return $clients;
    }

    /**
     * Create 3 sample posted journal entries (simple revenue transactions).
     */
    private function createSampleJournalEntries(int $tenantId): void
    {
        // Look up required accounts
        $cashAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', config('accounting.default_accounts.cash'))
            ->first();

        $revenueAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', config('accounting.default_accounts.revenue'))
            ->first();

        // If accounts don't exist yet, skip journal entry creation
        if (! $cashAccount || ! $revenueAccount) {
            return;
        }

        $entries = [
            [
                'date' => now()->startOfMonth()->toDateString(),
                'description' => 'إيرادات خدمات استشارية - عينة',
                'reference' => 'SAMPLE-001',
                'lines' => [
                    ['account_id' => $cashAccount->id, 'debit' => 5000, 'credit' => 0, 'description' => 'نقدية مستلمة'],
                    ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 5000, 'description' => 'إيرادات خدمات'],
                ],
            ],
            [
                'date' => now()->startOfMonth()->addDays(5)->toDateString(),
                'description' => 'إيرادات خدمات محاسبية - عينة',
                'reference' => 'SAMPLE-002',
                'lines' => [
                    ['account_id' => $cashAccount->id, 'debit' => 3000, 'credit' => 0, 'description' => 'نقدية مستلمة'],
                    ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 3000, 'description' => 'إيرادات محاسبة'],
                ],
            ],
            [
                'date' => now()->startOfMonth()->addDays(10)->toDateString(),
                'description' => 'إيرادات خدمات ضريبية - عينة',
                'reference' => 'SAMPLE-003',
                'lines' => [
                    ['account_id' => $cashAccount->id, 'debit' => 7500, 'credit' => 0, 'description' => 'نقدية مستلمة'],
                    ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 7500, 'description' => 'إيرادات ضريبية'],
                ],
            ],
        ];

        foreach ($entries as $entryData) {
            try {
                $journalEntry = $this->journalEntryService->create($entryData);
                $this->journalEntryService->post($journalEntry);
            } catch (\Throwable) {
                // Skip if journal entry creation fails (e.g., no fiscal period)
                continue;
            }
        }
    }

    /**
     * Create 2 sample invoices (1 sent, 1 paid with a payment).
     *
     * @param  array<int, Client>  $clients
     */
    private function createSampleInvoices(int $tenantId, array $clients): void
    {
        if (count($clients) < 2) {
            return;
        }

        $userId = Auth::id();

        // Invoice 1: Sent
        $invoice1 = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'client_id' => $clients[0]->id,
            'type' => 'invoice',
            'invoice_number' => 'SAMPLE-INV-001',
            'date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => InvoiceStatus::Sent,
            'subtotal' => 10000.00,
            'discount_amount' => 0,
            'vat_amount' => 1400.00,
            'total' => 11400.00,
            'amount_paid' => 0,
            'currency' => 'EGP',
            'notes' => 'فاتورة عينة - خدمات استشارية',
            'sent_at' => now()->subDays(10),
            'created_by' => $userId,
        ]);

        // Invoice 2: Paid (with a payment)
        $invoice2 = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'client_id' => $clients[1]->id,
            'type' => 'invoice',
            'invoice_number' => 'SAMPLE-INV-002',
            'date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => InvoiceStatus::Paid,
            'subtotal' => 5000.00,
            'discount_amount' => 0,
            'vat_amount' => 700.00,
            'total' => 5700.00,
            'amount_paid' => 5700.00,
            'currency' => 'EGP',
            'notes' => 'فاتورة عينة - خدمات محاسبية',
            'sent_at' => now()->subDays(20),
            'created_by' => $userId,
        ]);

        // Create a payment for invoice 2
        Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoice2->id,
            'amount' => 5700.00,
            'date' => now()->subDays(5)->toDateString(),
            'method' => 'bank_transfer',
            'reference' => 'SAMPLE-PAY-001',
            'notes' => 'دفعة عينة - تحويل بنكي',
            'created_by' => $userId,
        ]);
    }
}
