<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\TimesheetStatus;
use App\Domain\TimeTracking\Models\TimesheetEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimesheetService
{
    /**
     * List timesheet entries with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return TimesheetEntry::query()
            ->with(['user', 'client'])
            ->when(isset($filters['user_id']), fn ($q) => $q->forUser($filters['user_id']))
            ->when(isset($filters['client_id']), fn ($q) => $q->forClient($filters['client_id']))
            ->when(isset($filters['status']), fn ($q) => $q->ofStatus(TimesheetStatus::from($filters['status'])))
            ->when(
                isset($filters['from']) && isset($filters['to']),
                fn ($q) => $q->dateRange($filters['from'], $filters['to'])
            )
            ->when(isset($filters['is_billable']), fn ($q) => $filters['is_billable'] ? $q->billable() : $q->where('is_billable', false))
            ->when(isset($filters['search']), fn ($q) => $q->search($filters['search']))
            ->orderBy('date', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a timesheet entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TimesheetEntry
    {
        return DB::transaction(function () use ($data): TimesheetEntry {
            return TimesheetEntry::query()->create([
                'tenant_id' => (int) app('tenant.id'),
                'user_id' => $data['user_id'] ?? Auth::id(),
                'client_id' => $data['client_id'] ?? null,
                'date' => $data['date'],
                'task_description' => $data['task_description'],
                'hours' => $data['hours'],
                'is_billable' => $data['is_billable'] ?? true,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => TimesheetStatus::Draft,
            ]);
        });
    }

    /**
     * Update a timesheet entry.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(TimesheetEntry $entry, array $data): TimesheetEntry
    {
        if (! $entry->status->canEdit()) {
            throw ValidationException::withMessages([
                'status' => [
                    "Cannot edit entry with status: {$entry->status->value}.",
                    "لا يمكن تعديل قيد بحالة: {$entry->status->labelAr()}.",
                ],
            ]);
        }

        $entry->update($data);

        return $entry->refresh();
    }

    /**
     * Delete a timesheet entry.
     *
     * @throws ValidationException
     */
    public function delete(TimesheetEntry $entry): void
    {
        if (! $entry->status->canEdit()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Cannot delete a non-draft entry.',
                    'لا يمكن حذف قيد غير مسودة.',
                ],
            ]);
        }

        $entry->delete();
    }

    /**
     * Submit an entry for approval.
     *
     * @throws ValidationException
     */
    public function submit(TimesheetEntry $entry): TimesheetEntry
    {
        if (! $entry->status->canSubmit()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Entry cannot be submitted in its current status.',
                    'لا يمكن تقديم القيد بحالته الحالية.',
                ],
            ]);
        }

        $entry->update(['status' => TimesheetStatus::Submitted]);

        return $entry->refresh();
    }

    /**
     * Bulk submit entries.
     *
     * @param  array<int>  $entryIds
     */
    public function bulkSubmit(array $entryIds): int
    {
        return TimesheetEntry::query()
            ->whereIn('id', $entryIds)
            ->where('status', TimesheetStatus::Draft)
            ->update(['status' => TimesheetStatus::Submitted]);
    }

    /**
     * Approve an entry.
     *
     * @throws ValidationException
     */
    public function approve(TimesheetEntry $entry): TimesheetEntry
    {
        if (! $entry->status->canApprove()) {
            throw ValidationException::withMessages([
                'status' => [
                    'Only submitted entries can be approved.',
                    'يمكن اعتماد القيود المقدمة فقط.',
                ],
            ]);
        }

        $entry->update([
            'status' => TimesheetStatus::Approved,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $entry->refresh();
    }

    /**
     * Reject an entry.
     *
     * @throws ValidationException
     */
    public function reject(TimesheetEntry $entry, ?string $reason = null): TimesheetEntry
    {
        if (! $entry->status->canReject()) {
            throw ValidationException::withMessages([
                'status' => [
                    'This timesheet entry cannot be rejected.',
                    'لا يمكن رفض هذا القيد.',
                ],
            ]);
        }

        $notes = $entry->notes;
        if ($reason) {
            $notes = ($notes ? $notes."\n" : '')."سبب الرفض: {$reason}";
        }

        $entry->update([
            'status' => TimesheetStatus::Rejected,
            'notes' => $notes,
        ]);

        return $entry->refresh();
    }

    /**
     * Bulk approve entries.
     *
     * @param  array<int>  $entryIds
     */
    public function bulkApprove(array $entryIds): int
    {
        return TimesheetEntry::query()
            ->whereIn('id', $entryIds)
            ->where('status', TimesheetStatus::Submitted)
            ->update([
                'status' => TimesheetStatus::Approved,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
    }

    /**
     * Get timesheet summary with hours breakdown.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $baseQuery = TimesheetEntry::query()
            ->when(isset($filters['user_id']), fn ($q) => $q->forUser($filters['user_id']))
            ->when(isset($filters['client_id']), fn ($q) => $q->forClient($filters['client_id']))
            ->when(
                isset($filters['from']) && isset($filters['to']),
                fn ($q) => $q->dateRange($filters['from'], $filters['to'])
            )
            ->when(isset($filters['status']), fn ($q) => $q->ofStatus(TimesheetStatus::from($filters['status'])));

        $totalHours = (float) (clone $baseQuery)->sum('hours');
        $billableHours = (float) (clone $baseQuery)->where('is_billable', true)->sum('hours');

        $byClient = (clone $baseQuery)
            ->select('client_id', DB::raw('SUM(hours) as total_hours'))
            ->with('client:id,name')
            ->groupBy('client_id')
            ->get()
            ->map(fn ($row) => [
                'client_id' => $row->client_id,
                'client_name' => $row->client?->name,
                'hours' => (float) $row->total_hours,
            ])
            ->values()
            ->toArray();

        $byUser = (clone $baseQuery)
            ->select('user_id', DB::raw('SUM(hours) as total_hours'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'user_name' => $row->user?->name,
                'hours' => (float) $row->total_hours,
            ])
            ->values()
            ->toArray();

        $byDate = (clone $baseQuery)
            ->select('date', DB::raw('SUM(hours) as total_hours'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->toDateString(),
                'hours' => (float) $row->total_hours,
            ])
            ->values()
            ->toArray();

        return [
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'non_billable_hours' => round($totalHours - $billableHours, 2),
            'by_client' => $byClient,
            'by_user' => $byUser,
            'by_date' => $byDate,
        ];
    }
}
