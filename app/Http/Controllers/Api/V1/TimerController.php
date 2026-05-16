<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\TimeTracking\Models\Timer;
use App\Domain\TimeTracking\Services\TimerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeTracking\StartTimerRequest;
use App\Http\Resources\TimerResource;
use App\Http\Resources\TimesheetEntryResource;
use Illuminate\Http\JsonResponse;

class TimerController extends Controller
{
    public function __construct(
        private readonly TimerService $timerService,
    ) {}

    public function start(StartTimerRequest $request): TimerResource
    {
        return new TimerResource(
            $this->timerService->start($request->validated()),
        );
    }

    public function stop(Timer $timer): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timerService->stop($timer),
        );
    }

    public function current(): JsonResponse
    {
        $timer = $this->timerService->current();

        if (! $timer) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => new TimerResource($timer)]);
    }

    public function discard(Timer $timer): JsonResponse
    {
        $this->timerService->discard($timer);

        return response()->json(['message' => 'Timer discarded.']);
    }
}
