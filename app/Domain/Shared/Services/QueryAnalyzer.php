<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Production-safe N+1 query detector.
 *
 * Detects when the same query pattern is executed repeatedly (N+1 problem).
 * Reports via log + optional Slack alert.
 *
 * Register in AppServiceProvider::boot():
 *   QueryAnalyzer::register();
 */
class QueryAnalyzer
{
    /** @var array<string, array{count: int, sql: string, caller: string}> */
    private static array $queryPatterns = [];

    private static bool $registered = false;

    /** Minimum repeated queries to flag as N+1 */
    private static int $threshold = 5;

    /** Maximum unique patterns to track per request (memory safety) */
    private static int $maxPatterns = 200;

    /**
     * Register the query analyzer.
     * Call once in AppServiceProvider::boot().
     */
    public static function register(int $threshold = 5): void
    {
        if (self::$registered) {
            return;
        }
        if (! config('app.debug') && ! config('api.logging.enabled')) {
            return;
        }

        self::$threshold = $threshold;
        self::$registered = true;

        DB::listen(function ($query) {
            if (count(self::$queryPatterns) >= self::$maxPatterns) {
                return;
            }

            // Normalize: replace specific values with ?
            $normalized = preg_replace('/= ?\?|= ?\'[^\']*\'|= ?[0-9]+/', '= ?', $query->sql);
            $normalized = preg_replace('/in \([^)]+\)/', 'in (?)', $normalized);

            $key = md5($normalized);

            if (! isset(self::$queryPatterns[$key])) {
                self::$queryPatterns[$key] = [
                    'count' => 0,
                    'sql' => $normalized,
                    'time_ms' => 0,
                    'first_seen' => microtime(true),
                ];
            }

            self::$queryPatterns[$key]['count']++;
            self::$queryPatterns[$key]['time_ms'] += $query->time;
        });

        // Report at end of request
        app()->terminating(function () {
            self::report();
            self::reset();
        });
    }

    /**
     * Analyze and report N+1 patterns.
     */
    private static function report(): void
    {
        $violations = [];

        foreach (self::$queryPatterns as $pattern) {
            if ($pattern['count'] >= self::$threshold) {
                $violations[] = $pattern;
            }
        }

        if (empty($violations)) {
            return;
        }

        // Sort by count descending
        usort($violations, fn ($a, $b) => $b['count'] - $a['count']);

        $requestPath = '';
        try {
            $requestPath = request()?->method().' '.request()?->path();
        } catch (\Throwable) {
        }

        $report = [
            'request' => $requestPath,
            'violations' => count($violations),
            'details' => array_map(fn ($v) => [
                'query' => mb_substr($v['sql'], 0, 200),
                'count' => $v['count'],
                'total_time_ms' => round($v['time_ms'], 2),
            ], array_slice($violations, 0, 5)), // Top 5
        ];

        Log::warning('N+1 query detected', $report);

        // Alert via ErrorReporter for severe cases
        if ($violations[0]['count'] >= self::$threshold * 3) {
            ErrorReporter::alert(
                title: 'Severe N+1 Query Detected',
                message: "Request: {$requestPath}\nQuery executed {$violations[0]['count']} times:\n".mb_substr($violations[0]['sql'], 0, 200),
                level: 'warning',
                context: ['violations' => count($violations)],
            );
        }
    }

    /**
     * Reset for next request.
     */
    private static function reset(): void
    {
        self::$queryPatterns = [];
    }

    /**
     * Get current request stats (for debugging).
     */
    public static function getStats(): array
    {
        $total = array_sum(array_column(self::$queryPatterns, 'count'));
        $unique = count(self::$queryPatterns);
        $violations = count(array_filter(self::$queryPatterns, fn ($p) => $p['count'] >= self::$threshold));

        return [
            'total_queries' => $total,
            'unique_patterns' => $unique,
            'n_plus_one_violations' => $violations,
        ];
    }
}
