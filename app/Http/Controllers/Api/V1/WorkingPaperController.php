<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Engagement\Models\Engagement;
use App\Domain\Engagement\Models\WorkingPaper;
use App\Domain\Engagement\Services\EngagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Engagement\StoreWorkingPaperRequest;
use App\Http\Requests\Engagement\UpdateWorkingPaperRequest;
use App\Http\Resources\WorkingPaperResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkingPaperController extends Controller
{
    public function __construct(
        private readonly EngagementService $engagementService,
    ) {}

    public function index(Engagement $engagement): AnonymousResourceCollection
    {
        return WorkingPaperResource::collection(
            $engagement->workingPapers()
                ->with(['assignedTo', 'reviewedByUser'])
                ->orderBy('sort_order')
                ->get(),
        );
    }

    public function store(StoreWorkingPaperRequest $request, Engagement $engagement): WorkingPaperResource
    {
        return new WorkingPaperResource(
            $this->engagementService->addWorkingPaper($engagement, $request->validated()),
        );
    }

    public function update(UpdateWorkingPaperRequest $request, WorkingPaper $workingPaper): WorkingPaperResource
    {
        return new WorkingPaperResource(
            $this->engagementService->updateWorkingPaper($workingPaper, $request->validated()),
        );
    }

    public function review(WorkingPaper $workingPaper): WorkingPaperResource
    {
        return new WorkingPaperResource(
            $this->engagementService->reviewWorkingPaper($workingPaper),
        );
    }
}
