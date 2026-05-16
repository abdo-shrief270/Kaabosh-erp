<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

/**
 * Compliance audit service. Stubbed during the muhasebi → kaabosh-erp split:
 * the original reports keyed off Invoice/JournalEntry/Account etc. which no
 * longer exist in ERP. To be rebuilt for ERP-relevant audit surfaces
 * (payroll-run approvals, sensitive employee data access, salary changes,
 * leave approvals) when the audit module is prioritised.
 */
class AuditComplianceService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function userAccessReport(array $filters): array
    {
        return ['rows' => [], 'meta' => ['total' => 0]];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function changeReport(array $filters): array
    {
        return ['rows' => [], 'meta' => ['total' => 0]];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function highRiskTransactions(array $filters): array
    {
        return ['rows' => [], 'meta' => ['total' => 0]];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function segregationOfDuties(array $filters): array
    {
        return ['violations' => []];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportAuditTrail(array $filters): array
    {
        return ['format' => 'csv', 'rows' => []];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function complianceSummary(array $filters): array
    {
        return [
            'access_events' => 0,
            'high_risk_events' => 0,
            'change_events' => 0,
            'violations' => 0,
        ];
    }
}
