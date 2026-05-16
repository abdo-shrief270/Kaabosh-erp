<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Services\SubscriptionService;
use App\Domain\Subscription\Services\UsageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\ChangePlanRequest;
use App\Http\Requests\Subscription\SubscribeRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly UsageService $usageService,
    ) {}

    /**
     * Get current subscription with plan details.
     */
    public function show(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (! $subscription) {
            return response()->json([
                'message' => 'لا يوجد اشتراك حالي.',
                'data' => null,
            ]);
        }

        return (new SubscriptionResource($subscription->load('plan')))
            ->response();
    }

    /**
     * Start or change a subscription.
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $tenantId = (int) app('tenant.id');

        $subscription = $this->subscriptionService->subscribe(
            tenantId: $tenantId,
            planId: $request->validated('plan_id'),
            billingCycle: $request->validated('billing_cycle'),
            gateway: $request->validated('gateway', 'paymob'),
        );

        return (new SubscriptionResource($subscription->load('plan')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Cancel the current subscription.
     */
    public function cancel(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (! $subscription) {
            return response()->json([
                'message' => 'لا يوجد اشتراك حالي للإلغاء.',
            ], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionService->cancel($subscription);

        return (new SubscriptionResource($subscription->load('plan')))
            ->response();
    }

    /**
     * Renew the current subscription.
     */
    public function renew(): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (! $subscription) {
            return response()->json([
                'message' => 'لا يوجد اشتراك حالي للتجديد.',
            ], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionService->renew($subscription);

        return (new SubscriptionResource($subscription->load('plan')))
            ->response();
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (! $subscription) {
            return response()->json([
                'message' => 'لا يوجد اشتراك حالي لتغيير الخطة.',
            ], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionService->changePlan(
            subscription: $subscription,
            newPlanId: $request->validated('plan_id'),
            billingCycle: $request->validated('billing_cycle'),
        );

        return (new SubscriptionResource($subscription->load('plan')))
            ->response();
    }

    /**
     * Get current usage vs plan limits.
     */
    public function usage(): JsonResponse
    {
        $usage = $this->usageService->getUsage();

        return response()->json([
            'data' => $usage,
        ]);
    }

    /**
     * Get usage history (last 30 days).
     */
    public function usageHistory(): JsonResponse
    {
        $history = $this->usageService->getUsageHistory();

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * List subscription payments.
     */
    public function payments(): AnonymousResourceCollection
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();

        if (! $subscription) {
            return SubscriptionPaymentResource::collection(collect());
        }

        $payments = $subscription->payments()
            ->orderByDesc('created_at')
            ->paginate(15);

        return SubscriptionPaymentResource::collection($payments);
    }
}
