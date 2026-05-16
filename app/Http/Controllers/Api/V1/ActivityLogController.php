<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shared\Services\ActivityLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $activities = $this->activityLogService->list([
            'user_id' => $request->query('user_id'),
            'subject_type' => $request->query('subject_type'),
            'subject_id' => $request->query('subject_id'),
            'event' => $request->query('event'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'search' => $request->query('search'),
            'per_page' => min((int) ($request->query('per_page', 20)), 100),
        ]);

        // Transform the paginated results
        $activities->getCollection()->transform(fn (Activity $a) => [
            'id' => $a->id,
            'description' => $a->description,
            'event' => $a->event,
            'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
            'subject_id' => $a->subject_id,
            'causer' => $a->causer ? [
                'id' => $a->causer->id,
                'name' => $a->causer->name,
            ] : null,
            'created_at' => $a->created_at->toISOString(),
        ]);

        return response()->json($activities);
    }

    public function show(int $activityId): JsonResponse
    {
        $activity = Activity::with('causer:id,name,email')
            ->where('properties->tenant_id', (string) app('tenant.id'))
            ->findOrFail($activityId);

        return response()->json([
            'data' => $this->activityLogService->detail($activity),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $data = $this->activityLogService->stats(
            from: $request->query('from'),
            to: $request->query('to'),
        );

        return response()->json(['data' => $data]);
    }
}
