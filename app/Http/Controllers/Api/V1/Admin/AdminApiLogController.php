<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Shared\Models\ApiRequestLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminApiLogController extends Controller
{
    /**
     * List API request logs with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ApiRequestLog::latest('created_at');

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'error') {
                $query->errors();
            } elseif ($status === 'slow') {
                $query->slow(config('api.logging.slow_threshold_ms', 1000));
            } elseif (is_numeric($status)) {
                $query->where('status_code', (int) $status);
            }
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->input('method')));
        }

        if ($request->filled('path')) {
            $query->where('path', 'like', '%'.$request->input('path').'%');
        }

        if ($request->filled('user_id')) {
            $query->forUser((int) $request->input('user_id'));
        }

        if ($request->filled('ip')) {
            $query->where('ip', $request->input('ip'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $logs = $query->paginate(min((int) ($request->input('per_page', 50)), 100));

        return response()->json($logs);
    }

    /**
     * Get summary statistics for API logs.
     */
    public function stats(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 24);
        $since = now()->subHours($hours);

        $total = ApiRequestLog::where('created_at', '>=', $since)->count();
        $errors = ApiRequestLog::where('created_at', '>=', $since)->where('status_code', '>=', 400)->count();
        $slow = ApiRequestLog::where('created_at', '>=', $since)->where('duration_ms', '>=', config('api.logging.slow_threshold_ms', 1000))->count();
        $avgDuration = (int) ApiRequestLog::where('created_at', '>=', $since)->avg('duration_ms');

        // Top endpoints by request count
        $topEndpoints = ApiRequestLog::where('created_at', '>=', $since)
            ->selectRaw('method, path, COUNT(*) as count, AVG(duration_ms) as avg_ms')
            ->groupBy('method', 'path')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Error breakdown
        $errorBreakdown = ApiRequestLog::where('created_at', '>=', $since)
            ->where('status_code', '>=', 400)
            ->selectRaw('status_code, COUNT(*) as count')
            ->groupBy('status_code')
            ->orderByDesc('count')
            ->get();

        // Requests per hour (for chart)
        $hourly = ApiRequestLog::where('created_at', '>=', $since)
            ->selectRaw("strftime('%Y-%m-%d %H:00', created_at) as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'data' => [
                'period_hours' => $hours,
                'total_requests' => $total,
                'error_count' => $errors,
                'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
                'slow_count' => $slow,
                'avg_duration_ms' => $avgDuration,
                'top_endpoints' => $topEndpoints,
                'error_breakdown' => $errorBreakdown,
                'hourly' => $hourly,
            ],
        ]);
    }
}
