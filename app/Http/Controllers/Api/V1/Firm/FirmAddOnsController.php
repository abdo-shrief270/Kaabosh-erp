<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Subscription\Models\AddOn;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use App\Domain\Subscription\Services\AddOnService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Firm-scoped add-on management. Mirrors SubscriptionAddOnController but
 * the subject is the firm, not the tenant.
 */
class FirmAddOnsController extends Controller
{
    public function __construct(
        private readonly AddOnService $addOns,
    ) {}

    /**
     * Catalog of available add-ons (the templates) — same list for every firm.
     */
    public function catalog(Request $request): JsonResponse
    {
        $request->user(); // firm_only middleware already authed
        $rows = $this->addOns->catalog();

        return response()->json([
            'data' => $rows->map(fn (AddOn $a): array => [
                'id'             => $a->id,
                'slug'           => $a->slug,
                'name_en'        => $a->name_en,
                'name_ar'        => $a->name_ar,
                'description_en' => $a->description_en,
                'description_ar' => $a->description_ar,
                'type'           => $a->type?->value,
                'price_monthly'  => $a->price_monthly,
                'price_annual'   => $a->price_annual,
                'price_once'     => $a->price_once,
                'currency'       => $a->currency,
                'billing_cycle'  => $a->billing_cycle,
                'boost'          => $a->boost,
                'feature_slug'   => $a->feature_slug,
                'credit_kind'    => $a->credit_kind,
                'credit_quantity'=> $a->credit_quantity,
                'sort_order'     => $a->sort_order,
            ])->values(),
        ]);
    }

    /**
     * Active add-ons attached to the firm's subscription.
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $rows = $this->addOns->activeForFirm($user->firm_id);

        return response()->json(['data' => $rows->map(fn (SubscriptionAddOn $r): array => $this->present($r))->values()]);
    }

    /**
     * Effective limits = plan + add-on boosts. Used by the SPA to show
     * "you have N seats / cap" after add-ons.
     */
    public function effectiveLimits(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['data' => $this->addOns->getEffectiveLimitsForFirm($user->firm_id)]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }
        if (! in_array($user->firm_role, [FirmRole::Owner, FirmRole::Partner], true)) {
            return response()->json(['message' => 'Only owners or partners can purchase add-ons.'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'slug'          => ['required', 'string'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,annual,once'],
            'quantity'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'gateway'       => ['nullable', 'string'],
        ]);

        $addOn = $this->addOns->findBySlug($data['slug']);
        if (! $addOn) {
            return response()->json(['message' => 'Add-on not found.'], Response::HTTP_NOT_FOUND);
        }

        $row = $this->addOns->purchaseForFirm($addOn, [
            'billing_cycle' => $data['billing_cycle'] ?? null,
            'quantity'      => $data['quantity'] ?? 1,
            'gateway'       => $data['gateway'] ?? null,
        ], $user->firm_id);

        return response()->json(['data' => $this->present($row)], Response::HTTP_CREATED);
    }

    public function cancel(Request $request, int $rowId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id || ! in_array($user->firm_role, [FirmRole::Owner, FirmRole::Partner], true)) {
            return response()->json(['message' => 'Only owners or partners can cancel add-ons.'], Response::HTTP_FORBIDDEN);
        }

        $row = SubscriptionAddOn::query()->withoutGlobalScopes()
            ->where('id', $rowId)
            ->where('firm_id', $user->firm_id)
            ->first();
        if (! $row) {
            return response()->json(['message' => 'Add-on not found in this firm.'], Response::HTTP_NOT_FOUND);
        }

        $immediate = (bool) $request->boolean('immediate', false);
        $updated = $immediate
            ? $this->addOns->cancelImmediately($row)
            : $this->addOns->cancelAtPeriodEnd($row);

        return response()->json(['data' => $this->present($updated)]);
    }

    private function present(SubscriptionAddOn $r): array
    {
        return [
            'id'                   => $r->id,
            'firm_id'              => $r->firm_id,
            'add_on'               => $r->addOn ? [
                'id'      => $r->addOn->id,
                'slug'    => $r->addOn->slug,
                'name_en' => $r->addOn->name_en,
                'name_ar' => $r->addOn->name_ar,
                'type'    => $r->addOn->type?->value,
                'boost'   => $r->addOn->boost,
            ] : null,
            'quantity'             => $r->quantity,
            'status'               => is_object($r->status) ? $r->status->value : $r->status,
            'billing_cycle'        => is_object($r->billing_cycle) ? $r->billing_cycle->value : $r->billing_cycle,
            'price'                => $r->price,
            'currency'             => $r->currency,
            'current_period_start' => $r->current_period_start?->toDateString(),
            'current_period_end'   => $r->current_period_end?->toDateString(),
            'cancel_at_period_end' => (bool) $r->cancel_at_period_end,
            'cancelled_at'         => $r->cancelled_at?->toIso8601String(),
            'expires_at'           => $r->expires_at?->toIso8601String(),
        ];
    }
}
