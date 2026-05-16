<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Operational readiness probe for the SuperAdmin panel.
 *
 * Returns 200 while DB + cache are reachable, 503 if either fails.
 * Horizon and Filament checks are informational only — they report
 * "not booted" when the packages aren't installed or active yet.
 */
class AdminHealthController extends Controller
{
    public function show(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'filament' => $this->checkFilament(),
        ];

        $critical = ['database', 'cache'];
        $degraded = false;

        foreach ($critical as $key) {
            if (($checks[$key]['status'] ?? null) !== 'ok') {
                $degraded = true;
                break;
            }
        }

        return response()->json([
            'status' => $degraded ? 'degraded' : 'ok',
            'checks' => $checks,
            'time' => now()->toISOString(),
        ], $degraded ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_OK);
    }

    /** @return array<string, mixed> */
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** @return array<string, mixed> */
    private function checkCache(): array
    {
        try {
            Cache::put('_health', '1', 10);
            $ok = Cache::get('_health') === '1';
            Cache::forget('_health');

            return ['status' => $ok ? 'ok' : 'error'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** @return array<string, mixed> */
    private function checkQueue(): array
    {
        $repoContract = '\\Laravel\\Horizon\\Contracts\\MasterSupervisorRepository';

        if (! interface_exists($repoContract)) {
            return ['status' => 'skipped', 'message' => 'horizon not installed'];
        }

        try {
            $supervisors = app($repoContract)->all();

            return [
                'status' => count($supervisors) > 0 ? 'ok' : 'degraded',
                'supervisors' => count($supervisors),
                'message' => count($supervisors) > 0 ? null : 'horizon not running',
            ];
        } catch (Throwable $e) {
            return ['status' => 'degraded', 'message' => 'horizon not booted'];
        }
    }

    /** @return array<string, mixed> */
    private function checkFilament(): array
    {
        $facade = '\\Filament\\Facades\\Filament';

        if (! class_exists($facade)) {
            return ['status' => 'skipped', 'message' => 'filament not installed'];
        }

        try {
            $panel = $facade::getPanel('admin');
            $resources = $panel ? $panel->getResources() : [];

            return [
                'status' => 'ok',
                'resources' => count($resources),
            ];
        } catch (Throwable $e) {
            return ['status' => 'degraded', 'message' => $e->getMessage()];
        }
    }
}
