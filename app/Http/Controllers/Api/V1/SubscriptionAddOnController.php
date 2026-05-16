<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Models\AddOn;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use App\Domain\Subscription\Services\AddOnService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\PurchaseAddOnRequest;
use App\Http\Resources\SubscriptionAddOnResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionAddOnController extends Controller
{
    public function __construct(
        private readonly AddOnService $addOnService,
    ) {}

    /**
     * List add-ons attached to the current tenant's subscription.
     * Filtered by status via ?status=active|cancelled|expired (default: all).
     */
    public function index(): AnonymousResourceCollection
    {
        $rows = request()->boolean('active_only')
            ? $this->addOnService->activeForTenant()
            : $this->addOnService->allForTenant();

        return SubscriptionAddOnResource::collection($rows);
    }

    public function show(SubscriptionAddOn $subscriptionAddOn): SubscriptionAddOnResource
    {
        // BelongsToTenant guards cross-tenant access; we still verify here.
        abort_if(
            (int) $subscriptionAddOn->tenant_id !== (int) app('tenant.id'),
            Response::HTTP_NOT_FOUND,
        );

        return new SubscriptionAddOnResource($subscriptionAddOn->load(['addOn', 'credits']));
    }

    /**
     * Purchase an add-on. Activated immediately on success — payment is
     * either captured inline (bank_transfer = manual confirm) or via the
     * existing Paymob/Fawry flows wired from the subscription service.
     * For v1 we activate first and reconcile gateway state via webhook;
     * a stricter "wait for capture" gate can be added once the volume
     * justifies it.
     */
    public function store(PurchaseAddOnRequest $request): SubscriptionAddOnResource
    {
        /** @var AddOn $addOn */
        $addOn = AddOn::query()->findOrFail($request->integer('add_on_id'));

        $row = $this->addOnService->purchase(
            addOn: $addOn,
            opts: array_filter([
                'quantity' => $request->integer('quantity') ?: 1,
                'billing_cycle' => $request->input('billing_cycle'),
                'gateway' => $request->input('payment_method'),
                'gateway_payment_id' => $request->input('gateway_payment_id'),
            ], fn ($v) => $v !== null && $v !== ''),
        );

        return new SubscriptionAddOnResource($row->load(['addOn', 'credits']));
    }

    /**
     * Per-tenant credit balances grouped by kind, e.g.
     *   { "sms": { "kind": "sms", "balance": 750 }, "ai_tokens": ... }
     *
     * Drives the SMS-balance pill on the messaging page and the AI-tokens
     * indicator wherever AI features are gated. Empty object when the tenant
     * has no usable credit packs.
     */
    public function credits(): JsonResponse
    {
        return response()->json([
            'data' => $this->addOnService->creditSummary(),
        ]);
    }

    /**
     * Cancel at period end. Hard cancel is reserved for admin endpoints.
     */
    public function destroy(SubscriptionAddOn $subscriptionAddOn): JsonResponse
    {
        abort_if(
            (int) $subscriptionAddOn->tenant_id !== (int) app('tenant.id'),
            Response::HTTP_NOT_FOUND,
        );

        $this->addOnService->cancelAtPeriodEnd($subscriptionAddOn);

        return response()->json([
            'data' => new SubscriptionAddOnResource($subscriptionAddOn->fresh(['addOn'])),
            'message' => 'Add-on cancelled at end of period.',
        ]);
    }
}
