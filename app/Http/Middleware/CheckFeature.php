<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Services\FeatureFlagService;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\PlanFeatureCache;
use App\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks if a feature is enabled via:
 * 1. Feature flag system (admin-configurable, tenant/plan-level toggles)
 * 2. Plan-level feature check (subscription plan's features array)
 *
 * Usage: Route::middleware('feature:e_invoice')
 */
class CheckFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenantId = app('tenant.id');

        // 1. Check feature flag system first (overrides plan features)
        if (FeatureFlagService::isEnabled($feature, $tenantId)) {
            return $next($request);
        }

        // 2. Fall back to plan-level feature check
        $subscription = $request->attributes->get('subscription')
            ?? Subscription::query()
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->first();

        if (! $subscription) {
            return ApiResponse::error(
                code: 'subscription_inactive',
                message: __('messages.error.subscription_inactive', [], 'ar') ?: 'اشتراكك غير نشط. يرجى تجديد اشتراكك.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        if ($subscription->plan_id && PlanFeatureCache::has((int) $subscription->plan_id, $feature)) {
            return $next($request);
        }

        return ApiResponse::error(
            code: 'feature_not_available',
            message: __('messages.error.feature_not_available', [], 'ar') ?: 'هذه الميزة غير متوفرة في خطتك الحالية.',
            status: Response::HTTP_FORBIDDEN,
        );
    }
}
