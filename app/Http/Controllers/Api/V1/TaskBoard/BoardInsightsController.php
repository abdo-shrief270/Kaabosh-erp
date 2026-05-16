<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Services\BoardInsightsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardInsightsController extends Controller
{
    public function show(Request $request, Board $board, BoardInsightsService $service): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $days = (int) min(max($request->integer('days', 30), 7), 180);
        return response()->json(['data' => $service->snapshot($board, $days)]);
    }
}
