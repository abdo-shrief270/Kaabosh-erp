<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\Services\AuditComplianceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditComplianceController extends Controller
{
    public function __construct(
        private readonly AuditComplianceService $auditComplianceService,
    ) {}

    public function userAccess(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->userAccessReport([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'user_id' => $request->query('user_id'),
        ]);

        return response()->json(['data' => $data]);
    }

    public function changes(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->changeReport([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'user_id' => $request->query('user_id'),
            'model_type' => $request->query('model_type'),
            'threshold' => $request->query('threshold', '500000'),
            'per_page' => min((int) ($request->query('per_page', 50)), 100),
        ]);

        return response()->json(['data' => $data]);
    }

    public function highRisk(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->highRiskTransactions([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'threshold' => $request->query('threshold', '500000'),
        ]);

        return response()->json(['data' => $data]);
    }

    public function segregation(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->segregationOfDuties([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);

        return response()->json(['data' => $data]);
    }

    public function export(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->exportAuditTrail([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'user_id' => $request->query('user_id'),
            'model_type' => $request->query('model_type'),
            'format' => $request->query('format', 'json'),
        ]);

        return response()->json(['data' => $data]);
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $this->auditComplianceService->complianceSummary([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'threshold' => $request->query('threshold', '500000'),
        ]);

        return response()->json(['data' => $data]);
    }
}
