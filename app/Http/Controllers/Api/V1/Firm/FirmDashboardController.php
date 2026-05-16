<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Firm\Models\Firm;
use App\Domain\Firm\Services\FirmDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirmDashboardController extends Controller
{
    public function __construct(
        private readonly FirmDashboardService $dashboard,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $firm = Firm::find($user->firm_id);
        if (! $firm) {
            return response()->json(['message' => 'Firm not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->dashboard->snapshot($firm)]);
    }
}
