<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Firm\Models\Firm;
use App\Domain\Firm\Services\FirmBillingService;
use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirmBillingController extends Controller
{
    public function __construct(
        private readonly FirmBillingService $billing,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $firm = Firm::find($user->firm_id);
        if (! $firm) {
            return response()->json(['message' => 'Firm not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => array_merge(
                $this->billing->breakdown($firm),
                [
                    'firm' => [
                        'id'                   => $firm->id,
                        'name'                 => $firm->name,
                        'subscription_ends_at' => $firm->subscription_ends_at?->toIso8601String(),
                    ],
                    'available_plans' => $this->availablePlans(),
                ],
            ),
        ]);
    }

    /**
     * Change the firm's subscription tier. Blocks downgrade when current
     * usage exceeds the target plan's cap — the firm needs to deactivate
     * staff or clients first.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can change the subscription plan.'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'tier' => ['required', 'string', 'in:firm_starter,firm_pro,firm_enterprise'],
        ]);

        $firm = Firm::find($user->firm_id);
        if (! $firm) {
            return response()->json(['message' => 'Firm not found.'], Response::HTTP_NOT_FOUND);
        }

        $target = FirmSubscriptionTier::from($data['tier']);

        // Capacity guard — make sure the firm fits in the target plan today.
        $activeStaff = User::where('firm_id', $firm->id)->where('is_active', true)->count();
        $activeClients = Tenant::where('firm_id', $firm->id)
            ->where('type', TenantType::ClientBooks->value)
            ->whereIn('status', [TenantStatus::Active->value, TenantStatus::Trial->value])
            ->count();

        $staffCap = $target->staffSeats();
        if ($staffCap !== null && $activeStaff > $staffCap) {
            return response()->json([
                'message' => "Cannot switch to {$target->label()}: {$activeStaff} active staff exceeds the plan's cap of {$staffCap}. Deactivate staff first.",
                'code'    => 'staff_over_cap',
                'usage'   => ['staff' => $activeStaff, 'staff_cap' => $staffCap],
            ], Response::HTTP_CONFLICT);
        }

        $clientCap = $target->clientTenantCap();
        if ($clientCap !== null && $activeClients > $clientCap) {
            return response()->json([
                'message' => "Cannot switch to {$target->label()}: {$activeClients} active clients exceeds the plan's cap of {$clientCap}. Deactivate clients first.",
                'code'    => 'clients_over_cap',
                'usage'   => ['clients' => $activeClients, 'clients_cap' => $clientCap],
            ], Response::HTTP_CONFLICT);
        }

        $firm->subscription_tier = $target;
        $firm->save();

        return response()->json([
            'data' => array_merge(
                $this->billing->breakdown($firm->fresh()),
                [
                    'firm' => [
                        'id'                   => $firm->id,
                        'name'                 => $firm->name,
                        'subscription_ends_at' => $firm->subscription_ends_at?->toIso8601String(),
                    ],
                    'available_plans' => $this->availablePlans(),
                ],
            ),
        ]);
    }

    /**
     * @return array<int,array{tier:string,label:string,price:?int,staff_seats:?int,client_cap:?int}>
     */
    private function availablePlans(): array
    {
        return array_map(fn (FirmSubscriptionTier $t): array => [
            'tier'        => $t->value,
            'label'       => $t->label(),
            'price'       => $t->monthlyPriceEgp(),
            'staff_seats' => $t->staffSeats(),
            'client_cap'  => $t->clientTenantCap(),
        ], FirmSubscriptionTier::cases());
    }
}
