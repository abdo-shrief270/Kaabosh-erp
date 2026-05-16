<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Shared\Models\ApiUsageMeter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUsageController extends Controller
{
    /**
     * Get usage summary for a tenant.
     */
    public function tenantUsage(Request $request, int $tenantId): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json([
            'data' => [
                'summary' => ApiUsageMeter::summary($tenantId, $from, $to),
                'daily' => ApiUsageMeter::daily($tenantId, 30),
            ],
        ]);
    }

    /**
     * Get platform-wide usage overview.
     */
    public function platformUsage(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $since = now()->subDays($days)->toDateString();

        $totals = ApiUsageMeter::where('date', '>=', $since)
            ->selectRaw('
                SUM(api_calls) as total_api_calls,
                SUM(invoices_created) as total_invoices,
                SUM(journal_entries_created) as total_entries,
                SUM(documents_uploaded) as total_documents,
                SUM(eta_submissions) as total_eta,
                COUNT(DISTINCT tenant_id) as active_tenants
            ')
            ->first();

        // Top tenants by API usage
        $topTenants = ApiUsageMeter::where('date', '>=', $since)
            ->selectRaw('tenant_id, SUM(api_calls) as total_calls')
            ->groupBy('tenant_id')
            ->orderByDesc('total_calls')
            ->limit(10)
            ->with('tenant:id,name,slug')
            ->get();

        // Daily platform totals for chart
        $daily = ApiUsageMeter::where('date', '>=', $since)
            ->selectRaw('date, SUM(api_calls) as api_calls, SUM(invoices_created) as invoices, COUNT(DISTINCT tenant_id) as tenants')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'period_days' => $days,
                'totals' => $totals,
                'top_tenants' => $topTenants,
                'daily' => $daily,
            ],
        ]);
    }
}
