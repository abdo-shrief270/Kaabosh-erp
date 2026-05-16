<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Engagement\Models\Engagement;
use App\Domain\Engagement\Services\EngagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Engagement\StoreDeliverableRequest;
use App\Http\Requests\Engagement\StoreEngagementRequest;
use App\Http\Requests\Engagement\UpdateEngagementRequest;
use App\Http\Resources\EngagementDeliverableResource;
use App\Http\Resources\EngagementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EngagementController extends Controller
{
    public function __construct(
        private readonly EngagementService $engagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return EngagementResource::collection(
            $this->engagementService->list([
                'client_id' => $request->query('client_id'),
                'status' => $request->query('status'),
                'engagement_type' => $request->query('engagement_type'),
                'manager_id' => $request->query('manager_id'),
                'search' => $request->query('search'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function store(StoreEngagementRequest $request): EngagementResource
    {
        return new EngagementResource(
            $this->engagementService->create($request->validated()),
        );
    }

    public function show(Engagement $engagement): EngagementResource
    {
        return new EngagementResource(
            $engagement->load(['client', 'manager', 'partner', 'workingPapers', 'deliverables']),
        );
    }

    public function update(UpdateEngagementRequest $request, Engagement $engagement): EngagementResource
    {
        return new EngagementResource(
            $this->engagementService->update($engagement, $request->validated()),
        );
    }

    public function destroy(Engagement $engagement): JsonResponse
    {
        $this->engagementService->delete($engagement);

        return response()->json(['message' => 'Engagement deleted successfully.']);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->engagementService->dashboard(),
        ]);
    }

    public function timeAllocation(Engagement $engagement): JsonResponse
    {
        return response()->json([
            'data' => $this->engagementService->timeAllocation($engagement),
        ]);
    }

    public function addDeliverable(StoreDeliverableRequest $request, Engagement $engagement): EngagementDeliverableResource
    {
        return new EngagementDeliverableResource(
            $this->engagementService->addDeliverable($engagement, $request->validated()),
        );
    }

    public function completeDeliverable(Engagement $engagement, int $deliverable): EngagementDeliverableResource
    {
        $deliverableModel = $engagement->deliverables()->findOrFail($deliverable);

        return new EngagementDeliverableResource(
            $this->engagementService->completeDeliverable($deliverableModel),
        );
    }
}
