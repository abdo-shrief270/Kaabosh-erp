<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the current tenant has an accessible (active or trial) subscription.
 */
class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = app('tenant.id');

        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if (! $subscription || ! $subscription->isAccessible()) {
            return response()->json([
                'message' => 'اشتراكك غير نشط. يرجى تجديد اشتراكك.',
                'error' => 'subscription_inactive',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('subscription', $subscription);

        return $next($request);
    }
}
