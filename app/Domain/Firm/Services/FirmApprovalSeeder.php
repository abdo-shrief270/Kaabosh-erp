<?php

declare(strict_types=1);

namespace App\Domain\Firm\Services;

use App\Domain\Firm\Models\Firm;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Workflow\Enums\ApproverType;
use App\Domain\Workflow\Models\ApprovalWorkflow;
use Illuminate\Support\Facades\Log;

/**
 * Seeds default approval workflows on a fresh client-tenant so destructive
 * or high-value operations from Accountant-level staff route to the firm
 * Owner. Idempotent: skips entity types that already have an active
 * workflow on the tenant.
 *
 * Uses ApproverType::FirmOwner so the approver is resolved dynamically
 * at approval-request time rather than baked-in to a user_id — ownership
 * transfers don't break pending or future workflows.
 */
class FirmApprovalSeeder
{
    /**
     * Entity types + their amount thresholds. null threshold means every
     * action of that type needs approval regardless of amount.
     */
    private const RULES = [
        'invoice'        => 5_000,    // posting an invoice ≥ 5k EGP to GL
        'journal_entry'  => 50_000,   // posting a JE ≥ 50k EGP
        'bill_payment'   => 25_000,   // bill payment ≥ 25k EGP
        'payroll_run'    => null,     // every payroll run
        'fiscal_period'  => null,     // every period close
    ];

    /**
     * @return int number of workflows actually created
     */
    public function seedFor(Tenant $tenant): int
    {
        $firm = Firm::find($tenant->firm_id);
        if (! $firm) return 0;

        $created = 0;
        foreach (self::RULES as $entityType => $limit) {
            $existing = ApprovalWorkflow::where('tenant_id', $tenant->id)
                ->where('entity_type', $entityType)
                ->where('is_active', true)
                ->exists();
            if ($existing) continue;

            try {
                $workflow = ApprovalWorkflow::create([
                    'tenant_id'   => $tenant->id,
                    'name_en'     => $this->workflowName($entityType, 'en'),
                    'name_ar'     => $this->workflowName($entityType, 'ar'),
                    'entity_type' => $entityType,
                    'is_active'   => true,
                ]);

                $workflow->steps()->create([
                    'step_order'      => 1,
                    'approver_type'   => ApproverType::FirmOwner->value,
                    'approver_id'     => null, // resolved at request time
                    'approval_limit'  => $limit,
                    'timeout_hours'   => 72,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::warning('FirmApprovalSeeder step failed', [
                    'tenant_id' => $tenant->id,
                    'entity'    => $entityType,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    /**
     * Run the seeder over every active client-tenant in the system. Idempotent
     * — existing workflows are skipped per tenant.
     *
     * @return array{tenants:int,tenants_with_new:int,workflows_created:int}
     */
    public function backfillAll(): array
    {
        $stats = ['tenants' => 0, 'tenants_with_new' => 0, 'workflows_created' => 0];

        $tenants = Tenant::where('type', TenantType::ClientBooks->value)
            ->whereIn('status', ['active', 'trial'])
            ->cursor();

        foreach ($tenants as $tenant) {
            $stats['tenants']++;
            $n = $this->seedFor($tenant);
            if ($n > 0) {
                $stats['tenants_with_new']++;
                $stats['workflows_created'] += $n;
            }
        }

        return $stats;
    }

    private function workflowName(string $entityType, string $locale): string
    {
        $labels = [
            'invoice'        => ['en' => 'Invoice posting — owner approval',      'ar' => 'ترحيل الفاتورة — اعتماد المالك'],
            'journal_entry'  => ['en' => 'Large journal entry — owner approval', 'ar' => 'قيد كبير — اعتماد المالك'],
            'bill_payment'   => ['en' => 'Bill payment — owner approval',        'ar' => 'دفع فاتورة مورد — اعتماد المالك'],
            'payroll_run'    => ['en' => 'Payroll run — owner approval',         'ar' => 'تشغيل الرواتب — اعتماد المالك'],
            'fiscal_period'  => ['en' => 'Period close — owner approval',        'ar' => 'إقفال فترة — اعتماد المالك'],
        ];

        return $labels[$entityType][$locale] ?? $entityType;
    }
}
