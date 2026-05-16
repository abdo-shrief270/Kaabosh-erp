<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Admin\Services\AdminSubscriptionService;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateSubscriptionRequest;
use App\Http\Resources\AdminSubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly AdminSubscriptionService $subscriptionService,
    ) {}

    public function assign(Request $request): AdminSubscriptionResource
    {
        $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,annual'],
            'status' => ['sometimes', 'string', 'in:trial,active'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        return new AdminSubscriptionResource(
            $this->subscriptionService->assignToTenant($request->all()),
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminSubscriptionResource::collection(
            $this->subscriptionService->list([
                'status' => $request->query('status'),
                'plan_id' => $request->query('plan_id'),
                'search' => $request->query('search'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function show(int $subscription): AdminSubscriptionResource
    {
        $sub = Subscription::withoutGlobalScopes()->findOrFail($subscription);

        return new AdminSubscriptionResource(
            $this->subscriptionService->getDetail($sub),
        );
    }

    public function update(AdminUpdateSubscriptionRequest $request, int $subscription): AdminSubscriptionResource
    {
        $sub = Subscription::withoutGlobalScopes()->findOrFail($subscription);

        return new AdminSubscriptionResource(
            $this->subscriptionService->override($sub, $request->validated()),
        );
    }

    public function refund(int $payment): JsonResponse
    {
        $pmt = SubscriptionPayment::withoutGlobalScopes()->findOrFail($payment);
        $this->subscriptionService->refund($pmt);

        return response()->json(['message' => 'Payment refunded successfully.']);
    }
}
