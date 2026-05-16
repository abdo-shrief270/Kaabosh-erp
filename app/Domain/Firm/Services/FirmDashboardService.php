<?php

declare(strict_types=1);

namespace App\Domain\Firm\Services;

use App\Domain\Firm\Models\Firm;

/**
 * Firm/company dashboard service. Stubbed during the muhasebi → kaabosh-erp
 * split: the original tallied AP/AR/invoices/bills for the firm's own books.
 * ERP will compute employees, payroll runs MTD, active engagements, billable
 * hours MTD, etc. — see #future ERP dashboard task.
 */
class FirmDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(Firm $firm): array
    {
        $userCount = $firm->users()->count();

        return [
            'kpis' => [
                'active_companies' => 1,
                'total_companies' => 1,
                'active_staff' => $userCount,
                'staff_with_2fa' => 0,
                'total_transactions_mtd' => 0,
                'invoices_mtd' => 0,
                'bills_mtd' => 0,
            ],
            'recent_activity' => [],
        ];
    }
}
