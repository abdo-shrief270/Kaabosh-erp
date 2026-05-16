<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\Services\ExecutiveDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $dashboardService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);

        return response()->json([
            'data' => $this->dashboardService->financialOverview($filters),
        ]);
    }

    public function revenueAnalysis(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);

        return response()->json([
            'data' => $this->dashboardService->revenueAnalysis($filters),
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);

        return response()->json([
            'data' => $this->dashboardService->cashFlowForecast($filters),
        ]);
    }

    public function profitability(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);

        return response()->json([
            'data' => $this->dashboardService->profitabilityMetrics($filters),
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);

        return response()->json([
            'data' => $this->dashboardService->kpiDashboard($filters),
        ]);
    }

    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'period_a' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}:\d{4}-\d{2}-\d{2}$/'],
            'period_b' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}:\d{4}-\d{2}-\d{2}$/'],
        ]);

        return response()->json([
            'data' => $this->dashboardService->comparisonReport(
                periodA: $request->query('period_a'),
                periodB: $request->query('period_b'),
            ),
        ]);
    }
}
