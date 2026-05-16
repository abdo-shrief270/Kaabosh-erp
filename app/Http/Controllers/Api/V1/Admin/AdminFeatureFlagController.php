<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Shared\Services\FeatureFlagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminFeatureFlagController extends Controller
{
    public function index(): JsonResponse
    {
        $flags = FeatureFlag::orderBy('key')->get();

        return response()->json(['data' => $flags]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|max:100|unique:feature_flags,key|regex:/^[a-z][a-z0-9_]*$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_enabled_globally' => 'boolean',
            'enabled_for_plans' => 'nullable|array',
            'enabled_for_plans.*' => 'integer|exists:plans,id',
            'enabled_for_tenants' => 'nullable|array',
            'enabled_for_tenants.*' => 'integer',
            'disabled_for_tenants' => 'nullable|array',
            'disabled_for_tenants.*' => 'integer',
            'rollout_percentage' => 'nullable|string|max:3',
        ]);

        $flag = FeatureFlag::create($data);
        FeatureFlagService::clearCache();

        return response()->json(['data' => $flag], Response::HTTP_CREATED);
    }

    public function update(Request $request, FeatureFlag $featureFlag): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_enabled_globally' => 'boolean',
            'enabled_for_plans' => 'nullable|array',
            'enabled_for_plans.*' => 'integer',
            'enabled_for_tenants' => 'nullable|array',
            'enabled_for_tenants.*' => 'integer',
            'disabled_for_tenants' => 'nullable|array',
            'disabled_for_tenants.*' => 'integer',
            'rollout_percentage' => 'nullable|string|max:3',
        ]);

        $featureFlag->update(array_filter($data, fn ($v) => $v !== null));
        FeatureFlagService::clearCache();

        return response()->json(['data' => $featureFlag->fresh()]);
    }

    public function destroy(FeatureFlag $featureFlag): JsonResponse
    {
        $featureFlag->delete();
        FeatureFlagService::clearCache();

        return response()->json(['message' => 'Feature flag deleted.']);
    }

    /**
     * Check feature status for a specific tenant (for debugging).
     */
    public function checkForTenant(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer',
            'plan_id' => 'nullable|integer',
        ]);

        $flags = FeatureFlagService::getAllForTenant(
            (int) $request->input('tenant_id'),
            $request->input('plan_id') ? (int) $request->input('plan_id') : null,
        );

        return response()->json(['data' => $flags]);
    }
}
