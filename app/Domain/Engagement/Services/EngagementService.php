<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Services;

use App\Domain\Engagement\Enums\EngagementStatus;
use App\Domain\Engagement\Enums\WorkingPaperStatus;
use App\Domain\Engagement\Models\Engagement;
use App\Domain\Engagement\Models\EngagementDeliverable;
use App\Domain\Engagement\Models\WorkingPaper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EngagementService
{
    /**
     * List engagements with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Engagement::query()
            ->with(['client', 'manager', 'partner'])
            ->when(isset($filters['client_id']), fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['engagement_type']), fn ($q) => $q->where('engagement_type', $filters['engagement_type']))
            ->when(isset($filters['manager_id']), fn ($q) => $q->where('manager_id', $filters['manager_id']))
            ->when(isset($filters['search']), fn ($q) => $q->where(function ($q2) use ($filters) {
                $q2->where('name_ar', 'ilike', "%{$filters['search']}%")
                    ->orWhere('name_en', 'ilike', "%{$filters['search']}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create an engagement.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Engagement
    {
        return DB::transaction(function () use ($data): Engagement {
            return Engagement::query()->create([
                'tenant_id' => (int) app('tenant.id'),
                'client_id' => $data['client_id'],
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'engagement_type' => $data['engagement_type'],
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'status' => $data['status'] ?? EngagementStatus::Planning,
                'manager_id' => $data['manager_id'] ?? null,
                'partner_id' => $data['partner_id'] ?? null,
                'planned_hours' => $data['planned_hours'] ?? 0,
                'budget_amount' => $data['budget_amount'] ?? 0,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Update an engagement.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Engagement $engagement, array $data): Engagement
    {
        $engagement->update($data);

        return $engagement->refresh();
    }

    /**
     * Delete an engagement.
     */
    public function delete(Engagement $engagement): void
    {
        $engagement->delete();
    }

    /**
     * Add a working paper to an engagement.
     *
     * @param  array<string, mixed>  $data
     */
    public function addWorkingPaper(Engagement $engagement, array $data): WorkingPaper
    {
        return WorkingPaper::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            'engagement_id' => $engagement->id,
            'section' => $data['section'],
            'reference_code' => $data['reference_code'] ?? null,
            'title_ar' => $data['title_ar'],
            'title_en' => $data['title_en'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? WorkingPaperStatus::NotStarted,
            'assigned_to' => $data['assigned_to'] ?? null,
            'document_id' => $data['document_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    /**
     * Update a working paper.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWorkingPaper(WorkingPaper $workingPaper, array $data): WorkingPaper
    {
        $workingPaper->update($data);

        return $workingPaper->refresh();
    }

    /**
     * Review a working paper (segregation: reviewer must differ from assigned user).
     *
     * @throws ValidationException
     */
    public function reviewWorkingPaper(WorkingPaper $workingPaper): WorkingPaper
    {
        $reviewerId = Auth::id();

        if ($workingPaper->assigned_to !== null && $workingPaper->assigned_to === $reviewerId) {
            throw ValidationException::withMessages([
                'reviewed_by' => [
                    'The reviewer must be a different user than the assigned preparer.',
                    'يجب أن يكون المراجع شخصًا مختلفًا عن المُعِد.',
                ],
            ]);
        }

        $workingPaper->update([
            'status' => WorkingPaperStatus::Reviewed,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $workingPaper->refresh();
    }

    /**
     * Add a deliverable to an engagement.
     *
     * @param  array<string, mixed>  $data
     */
    public function addDeliverable(Engagement $engagement, array $data): EngagementDeliverable
    {
        return EngagementDeliverable::query()->create([
            'engagement_id' => $engagement->id,
            'title_ar' => $data['title_ar'],
            'title_en' => $data['title_en'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);
    }

    /**
     * Mark a deliverable as completed.
     */
    public function completeDeliverable(EngagementDeliverable $deliverable): EngagementDeliverable
    {
        $deliverable->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        return $deliverable->refresh();
    }

    /**
     * Dashboard: active engagements with progress, upcoming deadlines, overdue items.
     *
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $tenantId = (int) app('tenant.id');

        $activeEngagements = Engagement::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                EngagementStatus::Planning,
                EngagementStatus::InProgress,
                EngagementStatus::Review,
            ])
            ->with(['client', 'manager'])
            ->get()
            ->map(fn (Engagement $e) => [
                'id' => $e->id,
                'name_ar' => $e->name_ar,
                'name_en' => $e->name_en,
                'client' => $e->client?->name,
                'status' => $e->status->value,
                'status_label' => $e->status->label(),
                'progress' => $e->progress(),
                'deadline' => $e->deadline?->toDateString(),
                'manager' => $e->manager?->name,
            ]);

        $upcomingDeadlines = Engagement::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays(30))
            ->whereNotIn('status', [EngagementStatus::Completed, EngagementStatus::Archived])
            ->orderBy('deadline')
            ->get(['id', 'name_ar', 'name_en', 'deadline', 'status']);

        $overdueItems = Engagement::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->whereNotIn('status', [EngagementStatus::Completed, EngagementStatus::Archived])
            ->orderBy('deadline')
            ->get(['id', 'name_ar', 'name_en', 'deadline', 'status']);

        return [
            'active_engagements' => $activeEngagements,
            'upcoming_deadlines' => $upcomingDeadlines,
            'overdue_items' => $overdueItems,
        ];
    }

    /**
     * Link time entries where description/project matches the engagement.
     *
     * @return array<string, mixed>
     */
    public function timeAllocation(Engagement $engagement): array
    {
        $entries = DB::table('timesheet_entries')
            ->where('tenant_id', $engagement->tenant_id)
            ->where('client_id', $engagement->client_id)
            ->where(function ($q) use ($engagement) {
                $q->where('task_description', 'ilike', "%{$engagement->name_ar}%");
                if ($engagement->name_en) {
                    $q->orWhere('task_description', 'ilike', "%{$engagement->name_en}%");
                }
            })
            ->get();

        $totalHours = $entries->sum('hours');

        return [
            'engagement_id' => $engagement->id,
            'matched_entries' => $entries->count(),
            'total_hours' => round((float) $totalHours, 2),
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'date' => $e->date,
                'hours' => $e->hours,
                'task_description' => $e->task_description,
            ])->values()->toArray(),
        ];
    }
}
