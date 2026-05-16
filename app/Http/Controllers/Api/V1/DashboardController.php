<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Onboarding\Services\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboardService->getKpis(),
        ]);
    }
}
