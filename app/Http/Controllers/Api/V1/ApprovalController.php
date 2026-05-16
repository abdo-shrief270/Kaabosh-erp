<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Workflow\Models\ApprovalRequest;
use App\Domain\Workflow\Services\ApprovalWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\ApprovalActionRequest;
use App\Http\Requests\Workflow\RejectApprovalRequest;
use App\Http\Requests\Workflow\SubmitApprovalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalWorkflowService $workflowService,
    ) {}

    /**
     * Submit an entity for approval.
     */
    public function submit(SubmitApprovalRequest $request): JsonResponse
    {
        $result = $this->workflowService->submitForApproval(
            $request->validated('entity_type'),
            (int) $request->validated('entity_id'),
            $request->validated('amount') !== null ? (float) $request->validated('amount') : null,
        );

        if (! $result) {
            return $this->success(null, 'No matching workflow found.');
        }

        return $this->created($result);
    }

    /**
     * Approve the current step.
     */
    public function approve(ApprovalActionRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $result = $this->workflowService->approve(
            $approvalRequest,
            $request->validated('comment'),
        );

        return $this->success($result);
    }

    /**
     * Reject the request.
     */
    public function reject(RejectApprovalRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $result = $this->workflowService->reject(
            $approvalRequest,
            $request->validated('comment'),
        );

        return $this->success($result);
    }

    /**
     * List pending approvals for the authenticated user.
     */
    public function pending(Request $request): JsonResponse
    {
        $pending = $this->workflowService->listPending(
            $request->user()->id,
        );

        return $this->success($pending);
    }

    /**
     * Get approval history for a specific entity.
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
        ]);

        $history = $this->workflowService->listForEntity(
            $request->query('entity_type'),
            (int) $request->query('entity_id'),
        );

        return $this->success($history);
    }
}
