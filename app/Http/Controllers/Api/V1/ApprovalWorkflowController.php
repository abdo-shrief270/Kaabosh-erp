<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Workflow\Models\ApprovalWorkflow;
use App\Domain\Workflow\Services\ApprovalWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreApprovalWorkflowRequest;
use App\Http\Requests\Workflow\UpdateApprovalWorkflowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalWorkflowController extends Controller
{
    public function __construct(
        private readonly ApprovalWorkflowService $workflowService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workflows = $this->workflowService->listWorkflows([
            'entity_type' => $request->query('entity_type'),
            'is_active' => $request->query('is_active'),
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return $this->success($workflows);
    }

    public function store(StoreApprovalWorkflowRequest $request): JsonResponse
    {
        $workflow = $this->workflowService->createWorkflow($request->validated());

        return $this->created($workflow);
    }

    public function show(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        $approvalWorkflow->load('steps');

        return $this->success($approvalWorkflow);
    }

    public function update(UpdateApprovalWorkflowRequest $request, ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        $workflow = $this->workflowService->updateWorkflow($approvalWorkflow, $request->validated());

        return $this->success($workflow);
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        $this->workflowService->deleteWorkflow($approvalWorkflow);

        return $this->success(null, 'Workflow deleted.');
    }
}
