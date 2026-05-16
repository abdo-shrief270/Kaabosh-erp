<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Admin\Services\AdminDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->dashboardService->getKpis()]);
    }

    public function monthlyRevenue(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 12);

        return response()->json(['data' => $this->dashboardService->getMonthlyRevenue($months)]);
    }

    public function revenueByPlan(): JsonResponse
    {
        return response()->json(['data' => $this->dashboardService->getRevenueByPlan()]);
    }
}
