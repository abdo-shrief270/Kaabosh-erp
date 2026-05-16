<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Models\PayrollItem;
use App\Domain\Payroll\Models\PayrollRun;
use App\Domain\Payroll\Services\PayslipPdfService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    public function __construct(
        private readonly PayslipPdfService $pdfService,
    ) {}

    public function download(PayrollRun $payrollRun, PayrollItem $payrollItem): Response
    {
        abort_if($payrollItem->payroll_run_id !== $payrollRun->id, 404);

        return $this->pdfService->generate($payrollItem);
    }
}
