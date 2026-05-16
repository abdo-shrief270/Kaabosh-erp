<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Paginated, filterable audit log viewer for admin.
 * Reads from spatie/laravel-activitylog's activity_log table.
 */
class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::latest();

        // Filter by subject type (e.g., "CmsPage", "BlogPost", "User")
        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%'.$request->input('subject_type').'%');
        }

        // Filter by subject ID
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        // Filter by causer (who performed the action)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        // Filter by event (created, updated, deleted)
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        // Filter by description keyword
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $logs = $query->with('causer:id,name,email')
            ->paginate(min((int) ($request->input('per_page', 30)), 100));

        // Transform for frontend
        $logs->getCollection()->transform(function (Activity $activity) {
            return [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'subject_type' => class_basename($activity->subject_type ?? ''),
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
                'properties' => $activity->properties?->toArray(),
                'created_at' => $activity->created_at?->toISOString(),
            ];
        });

        return response()->json($logs);
    }

    /**
     * Get audit log statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);
        $since = now()->subDays($days);

        $total = Activity::where('created_at', '>=', $since)->count();

        $byEvent = Activity::where('created_at', '>=', $since)
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event');

        $bySubject = Activity::where('created_at', '>=', $since)
            ->selectRaw('subject_type, COUNT(*) as count')
            ->groupBy('subject_type')
            ->get()
            ->map(fn ($row) => [
                'type' => class_basename($row->subject_type ?? 'Unknown'),
                'count' => $row->count,
            ]);

        $topUsers = Activity::where('created_at', '>=', $since)
            ->whereNotNull('causer_id')
            ->selectRaw('causer_id, COUNT(*) as count')
            ->groupBy('causer_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('causer:id,name')
            ->get()
            ->map(fn ($row) => [
                'user' => $row->causer?->name ?? 'Unknown',
                'count' => $row->count,
            ]);

        return response()->json([
            'data' => [
                'period_days' => $days,
                'total_events' => $total,
                'by_event' => $byEvent,
                'by_subject' => $bySubject,
                'top_users' => $topUsers,
            ],
        ]);
    }
}
