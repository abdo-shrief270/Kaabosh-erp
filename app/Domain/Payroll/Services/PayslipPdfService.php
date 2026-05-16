<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PayslipPdfService
{
    /**
     * Generate a pay slip PDF for a payroll item.
     */
    public function generate(PayrollItem $item): Response
    {
        $item->load(['employee.user', 'payrollRun']);

        $tenant = app('tenant');

        $pdf = Pdf::loadView('reports.payslip', [
            'item' => $item,
            'employee' => $item->employee,
            'user' => $item->employee->user,
            'run' => $item->payrollRun,
            'tenant' => $tenant,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $name = $item->employee->user->name;
        $month = $item->payrollRun->month;
        $year = $item->payrollRun->year;

        return $pdf->download("payslip-{$name}-{$month}-{$year}.pdf");
    }
}
