<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Shared\Models\ApiRequestLog;
use App\Domain\Shared\Services\CircuitBreaker;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * System performance metrics and health for admin dashboard.
 */
class AdminMetricsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 1);
        $since = now()->subHours($hours);

        // Request metrics from API logs
        $requestMetrics = ApiRequestLog::where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total_requests,
                AVG(duration_ms) as avg_duration_ms,
                MAX(duration_ms) as max_duration_ms,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as server_errors,
                SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) as client_errors,
                SUM(CASE WHEN duration_ms >= 1000 THEN 1 ELSE 0 END) as slow_requests,
                SUM(request_size) as total_request_bytes,
                SUM(response_size) as total_response_bytes
            ')
            ->first();

        // Database stats
        $dbStats = [
            'connection' => config('database.default'),
        ];

        try {
            $result = DB::select('SELECT count(*) as count FROM information_schema.processlist WHERE db = ?', [config('database.connections.'.config('database.default').'.database')]);
            $dbStats['active_connections'] = $result[0]->count ?? null;
        } catch (\Throwable) {
            $dbStats['active_connections'] = null;
        }

        // Cache stats
        $cacheStats = [
            'driver' => config('cache.default'),
        ];

        // Queue depth (approximate)
        $queueStats = [];
        try {
            if (config('queue.default') === 'database') {
                $queueStats['pending_jobs'] = DB::table('jobs')->count();
                $queueStats['failed_jobs'] = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable) {
            // Queue tables may not exist
        }

        // Circuit breaker statuses
        $circuitBreakers = CircuitBreaker::getAllStatuses();

        // Memory
        $memoryStats = [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        return response()->json([
            'data' => [
                'period_hours' => $hours,
                'requests' => [
                    'total' => (int) ($requestMetrics->total_requests ?? 0),
                    'avg_duration_ms' => round((float) ($requestMetrics->avg_duration_ms ?? 0), 1),
                    'max_duration_ms' => (int) ($requestMetrics->max_duration_ms ?? 0),
                    'server_errors' => (int) ($requestMetrics->server_errors ?? 0),
                    'client_errors' => (int) ($requestMetrics->client_errors ?? 0),
                    'slow_requests' => (int) ($requestMetrics->slow_requests ?? 0),
                    'error_rate' => $requestMetrics->total_requests > 0
                        ? round((($requestMetrics->server_errors ?? 0) / $requestMetrics->total_requests) * 100, 2)
                        : 0,
                    'total_bandwidth_mb' => round(
                        (($requestMetrics->total_request_bytes ?? 0) + ($requestMetrics->total_response_bytes ?? 0)) / 1024 / 1024, 2
                    ),
                ],
                'database' => $dbStats,
                'cache' => $cacheStats,
                'queue' => $queueStats,
                'circuit_breakers' => $circuitBreakers,
                'memory' => $memoryStats,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ]);
    }

    /**
     * Reset a circuit breaker manually.
     */
    public function resetCircuitBreaker(Request $request): JsonResponse
    {
        $request->validate(['service' => 'required|string|max:50']);

        CircuitBreaker::reset($request->input('service'));

        return response()->json([
            'message' => "Circuit breaker reset for '{$request->input('service')}'.",
        ]);
    }
}
