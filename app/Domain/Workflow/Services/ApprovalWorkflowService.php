<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Services;

use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Workflow\Enums\ApprovalStatus;
use App\Domain\Workflow\Enums\ApproverType;
use App\Domain\Workflow\Models\ApprovalAction;
use App\Domain\Workflow\Models\ApprovalRequest;
use App\Domain\Workflow\Models\ApprovalStep;
use App\Domain\Workflow\Models\ApprovalWorkflow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ApprovalWorkflowService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}


    // ──────────────────────────────────────
    // Workflow CRUD
    // ──────────────────────────────────────

    /**
     * Create a workflow with its steps.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWorkflow(array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($data): ApprovalWorkflow {
            $workflow = ApprovalWorkflow::create([
                'tenant_id' => $data['tenant_id'] ?? app('tenant.id'),
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'entity_type' => $data['entity_type'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order' => $step['step_order'] ?? ($index + 1),
                        'approver_type' => $step['approver_type'],
                        'approver_id' => $step['approver_id'] ?? null,
                        'approval_limit' => $step['approval_limit'] ?? null,
                        'timeout_hours' => $step['timeout_hours'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    /**
     * Update a workflow and replace its steps.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWorkflow(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $data): ApprovalWorkflow {
            $workflow->update(collect($data)->only([
                'name_ar', 'name_en', 'entity_type', 'is_active',
            ])->toArray());

            if (isset($data['steps'])) {
                $workflow->steps()->delete();

                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order' => $step['step_order'] ?? ($index + 1),
                        'approver_type' => $step['approver_type'],
                        'approver_id' => $step['approver_id'] ?? null,
                        'approval_limit' => $step['approval_limit'] ?? null,
                        'timeout_hours' => $step['timeout_hours'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    /**
     * Delete a workflow (cascades to steps).
     */
    public function deleteWorkflow(ApprovalWorkflow $workflow): void
    {
        $workflow->delete();
    }

    /**
     * List workflows with pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listWorkflows(array $filters = []): LengthAwarePaginator
    {
        return ApprovalWorkflow::query()
            ->with('steps')
            ->when(isset($filters['entity_type']), fn ($q) => $q->where('entity_type', $filters['entity_type']))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    // ──────────────────────────────────────
    // Approval Flow
    // ──────────────────────────────────────

    /**
     * Decide whether an entity is cleared to proceed past an approval gate.
     *
     * Returns true when:
     *   - no active workflow exists for this entity type (no gate), OR
     *   - an active workflow exists but the amount doesn't trigger any step
     *     (e.g. all steps have approval_limit set and amount is below all of them), OR
     *   - an ApprovalRequest for this entity exists with status=Approved.
     *
     * Returns false when a workflow applies and no approved request exists
     * (including the case where a request is still pending/in_progress or rejected).
     */
    public function isApproved(string $entityType, int $entityId, ?float $amount = null): bool
    {
        // Owner-granted bypass: if the current actor has firm-bypass on the
        // active tenant, treat any approval gate as satisfied. Lets a senior
        // staff member skip gates the Owner has explicitly trusted them on.
        $actor = Auth::user();
        if ($actor && app()->bound('tenant')) {
            $tenant = app('tenant');
            if ($tenant instanceof \App\Domain\Tenant\Models\Tenant
                && method_exists($actor, 'hasApprovalBypassOn')
                && $actor->hasApprovalBypassOn($tenant)
            ) {
                return true;
            }
        }

        $workflow = ApprovalWorkflow::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->with('steps')
            ->first();

        if (! $workflow) {
            return true;
        }

        // If every step has a limit that the amount doesn't meet, no step applies.
        // A step with approval_limit=null always applies (unconditional gate).
        if ($amount !== null) {
            $applicable = $workflow->steps->contains(function (ApprovalStep $step) use ($amount): bool {
                return $step->approval_limit === null || $amount > (float) $step->approval_limit;
            });

            if (! $applicable) {
                return true;
            }
        }

        return ApprovalRequest::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', ApprovalStatus::Approved)
            ->exists();
    }

    /**
     * Submit an entity for approval.
     * Finds the matching active workflow, creates an ApprovalRequest.
     */
    public function submitForApproval(string $entityType, int $entityId, ?float $amount = null): ?ApprovalRequest
    {
        $workflow = ApprovalWorkflow::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();

        if (! $workflow) {
            return null;
        }

        // If amount is provided, check if any step has an approval_limit
        // and whether the amount exceeds it
        if ($amount !== null) {
            $applicableSteps = $workflow->steps->filter(function (ApprovalStep $step) use ($amount): bool {
                return $step->approval_limit === null || $amount > (float) $step->approval_limit;
            });

            if ($applicableSteps->isEmpty()) {
                return null;
            }
        }

        $request = ApprovalRequest::create([
            'tenant_id' => app('tenant.id'),
            'workflow_id' => $workflow->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'current_step' => 1,
            'status' => ApprovalStatus::InProgress,
            'requested_by' => Auth::id(),
        ]);

        $this->notifyApprovers($request, NotificationType::ApprovalRequired);

        return $request;
    }

    /**
     * Resolve the user ids that should be notified about an approval request's
     * current step. Handles all four approver types, including the dynamic
     * FirmOwner type (every owner of the tenant's firm).
     *
     * @return array<int>
     */
    private function resolveStepApprovers(ApprovalRequest $request): array
    {
        $request->loadMissing(['workflow.steps', 'workflow.tenant']);
        $step = $request->workflow->steps->firstWhere('step_order', $request->current_step);
        if (! $step) return [];

        $type = $step->approver_type instanceof ApproverType ? $step->approver_type : ApproverType::tryFrom((string) $step->approver_type);

        // User queries here run across tenant boundaries (an approver on a
        // client-tenant request typically belongs to the firm-books tenant).
        // withoutGlobalScopes bypasses BelongsToTenant.
        return match ($type) {
            ApproverType::User => $step->approver_id ? [(int) $step->approver_id] : [],

            ApproverType::Role => $step->approver_id
                ? User::withoutGlobalScopes()
                    ->whereHas('roles', fn ($q) => $q->where('id', $step->approver_id))
                    ->pluck('id')->all()
                : [],

            ApproverType::Manager => User::withoutGlobalScopes()
                ->where('id', $request->requested_by)
                ->value('manager_id') !== null
                    ? [(int) User::withoutGlobalScopes()->where('id', $request->requested_by)->value('manager_id')]
                    : [],

            ApproverType::FirmOwner => User::withoutGlobalScopes()
                ->where('firm_id', optional($request->workflow->tenant)->firm_id)
                ->where('firm_role', FirmRole::Owner->value)
                ->where('is_active', true)
                ->pluck('id')->all(),

            default => [],
        };
    }

    private function notifyApprovers(ApprovalRequest $request, NotificationType $type, ?string $note = null): void
    {
        try {
            $ids = $this->resolveStepApprovers($request);
            $entityLabel = (string) $request->entity_type;

            foreach ($ids as $userId) {
                $titleAr = match ($type) {
                    NotificationType::ApprovalRequired => "مطلوب اعتماد: {$entityLabel} #{$request->entity_id}",
                    NotificationType::ApprovalDecided  => "تمّ البتّ: {$entityLabel} #{$request->entity_id}",
                    default => 'إشعار اعتماد',
                };
                $titleEn = match ($type) {
                    NotificationType::ApprovalRequired => "Approval needed: {$entityLabel} #{$request->entity_id}",
                    NotificationType::ApprovalDecided  => "Approval decided: {$entityLabel} #{$request->entity_id}",
                    default => 'Approval notification',
                };

                $this->notifications->send(
                    userId:    $userId,
                    type:      $type,
                    titleAr:   $titleAr,
                    titleEn:   $titleEn,
                    bodyAr:    $note,
                    bodyEn:    $note,
                    actionUrl: '/approvals',
                    data: [
                        'approval_request_id' => $request->id,
                        'entity_type'         => $request->entity_type,
                        'entity_id'           => $request->entity_id,
                        'tenant_id'           => $request->tenant_id,
                    ],
                );
            }
        } catch (\Throwable $e) {
            // Best-effort — never fail the approval flow on a notification error.
            Log::warning('approval notification dispatch failed', [
                'approval_request_id' => $request->id,
                'type'                => $type->value,
                'error'               => $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve the current step. Advances to next step or marks approved.
     */
    public function approve(ApprovalRequest $request, ?string $comment = null): ApprovalRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['This request is no longer pending approval.'],
            ]);
        }

        return DB::transaction(function () use ($request, $comment): ApprovalRequest {
            ApprovalAction::create([
                'approval_request_id' => $request->id,
                'step_order' => $request->current_step,
                'action' => 'approved',
                'acted_by' => Auth::id(),
                'comment' => $comment,
                'acted_at' => Carbon::now(),
            ]);

            $totalSteps = $request->workflow->steps()->count();

            if ($request->current_step >= $totalSteps) {
                $request->update([
                    'status' => ApprovalStatus::Approved,
                ]);
                $fresh = $request->fresh(['workflow.steps', 'actions']);
                $this->notifyRequester($fresh, NotificationType::ApprovalDecided, 'approved', $comment);
                return $fresh;
            }

            $request->update([
                'current_step' => $request->current_step + 1,
                'status' => ApprovalStatus::InProgress,
            ]);
            $fresh = $request->fresh(['workflow.steps', 'actions']);
            // Notify the next approver in the chain.
            $this->notifyApprovers($fresh, NotificationType::ApprovalRequired);

            return $fresh;
        });
    }

    private function notifyRequester(ApprovalRequest $request, NotificationType $type, string $verb, ?string $comment = null): void
    {
        if (! $request->requested_by) return;

        try {
            $entityLabel = (string) $request->entity_type;
            $this->notifications->send(
                userId:    (int) $request->requested_by,
                type:      $type,
                titleAr:   "تمّ {$verb}: {$entityLabel} #{$request->entity_id}",
                titleEn:   ucfirst($verb).": {$entityLabel} #{$request->entity_id}",
                bodyAr:    $comment,
                bodyEn:    $comment,
                actionUrl: '/approvals',
                data: [
                    'approval_request_id' => $request->id,
                    'entity_type'         => $request->entity_type,
                    'entity_id'           => $request->entity_id,
                    'decision'            => $verb,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('approval requester notification failed', [
                'approval_request_id' => $request->id,
                'error'               => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject the request with a required comment.
     */
    public function reject(ApprovalRequest $request, string $comment): ApprovalRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['This request is no longer pending approval.'],
            ]);
        }

        return DB::transaction(function () use ($request, $comment): ApprovalRequest {
            ApprovalAction::create([
                'approval_request_id' => $request->id,
                'step_order' => $request->current_step,
                'action' => 'rejected',
                'acted_by' => Auth::id(),
                'comment' => $comment,
                'acted_at' => Carbon::now(),
            ]);

            $request->update([
                'status' => ApprovalStatus::Rejected,
            ]);

            $fresh = $request->fresh(['workflow.steps', 'actions']);
            $this->notifyRequester($fresh, NotificationType::ApprovalDecided, 'rejected', $comment);

            return $fresh;
        });
    }

    /**
     * Determine who needs to approve next based on the current step.
     *
     * @return array{type: ApproverType, id: int|null}|null
     */
    public function getNextApprover(ApprovalRequest $request): ?array
    {
        if (! $request->isPending()) {
            return null;
        }

        $step = $request->workflow->steps
            ->where('step_order', $request->current_step)
            ->first();

        if (! $step) {
            return null;
        }

        return [
            'type' => $step->approver_type,
            'id' => $step->approver_id,
        ];
    }

    /**
     * Get all pending approvals for a user (by direct assignment or role).
     */
    public function listPending(int $userId): Collection
    {
        $user = User::findOrFail($userId);
        $roleNames = $user->getRoleNames()->toArray();

        // Get role IDs from Spatie
        $roleIds = Role::query()
            ->whereIn('name', $roleNames)
            ->pluck('id')
            ->toArray();

        // Firm-owner matches: workflows with approver_type = firm_owner are
        // owned by every Owner of the firm whose tenant the workflow targets.
        $isFirmOwner = $user->firm_role
            && \App\Domain\Shared\Enums\FirmRole::Owner === $user->firm_role;
        $firmId = $user->firm_id;

        return ApprovalRequest::query()
            ->whereIn('status', [ApprovalStatus::Pending, ApprovalStatus::InProgress])
            ->whereHas('workflow.steps', function ($q) use ($userId, $roleIds, $isFirmOwner, $firmId) {
                $q->whereColumn('approval_steps.step_order', 'approval_requests.current_step')
                    ->where(function ($q) use ($userId, $roleIds, $isFirmOwner, $firmId): void {
                        $q->where(function ($q) use ($userId): void {
                            $q->where('approver_type', ApproverType::User)
                                ->where('approver_id', $userId);
                        })->orWhere(function ($q) use ($roleIds): void {
                            $q->where('approver_type', ApproverType::Role)
                                ->whereIn('approver_id', $roleIds);
                        })->orWhere('approver_type', ApproverType::Manager);

                        // FirmOwner — match when this user is the Owner of the
                        // firm that owns the workflow's tenant. Resolved
                        // dynamically so ownership transfer doesn't break
                        // pending approvals.
                        if ($isFirmOwner && $firmId) {
                            $q->orWhere(function ($q) use ($firmId): void {
                                $q->where('approver_type', ApproverType::FirmOwner)
                                  ->whereHas('workflow.tenant', function ($t) use ($firmId): void {
                                      $t->where('firm_id', $firmId);
                                  });
                            });
                        }
                    });
            })
            ->with(['workflow', 'requester'])
            ->get();
    }

    /**
     * Get approval history for an entity.
     */
    public function listForEntity(string $type, int $id): Collection
    {
        return ApprovalRequest::query()
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->with(['workflow', 'actions.actor', 'requester'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
