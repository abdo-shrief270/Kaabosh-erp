<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Services\PlanService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    /**
     * List active plans (public — no auth needed).
     */
    public function index(): AnonymousResourceCollection
    {
        $plans = $this->planService->listPlans(activeOnly: true);

        return PlanResource::collection($plans);
    }

    /**
     * Show a single plan (public).
     */
    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }

    /**
     * Create a new plan (super_admin only).
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService->createPlan($request->validated());

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update an existing plan (super_admin only).
     */
    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        $plan = $this->planService->updatePlan($plan, $request->validated());

        return new PlanResource($plan);
    }

    /**
     * Deactivate a plan (super_admin only).
     */
    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->deactivatePlan($plan);

        return response()->json([
            'message' => 'Plan deactivated successfully.',
        ]);
    }
}
