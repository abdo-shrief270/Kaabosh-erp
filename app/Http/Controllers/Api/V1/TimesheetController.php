<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\TimeTracking\Models\TimesheetEntry;
use App\Domain\TimeTracking\Services\TimesheetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeTracking\StoreTimesheetEntryRequest;
use App\Http\Requests\TimeTracking\UpdateTimesheetEntryRequest;
use App\Http\Resources\TimesheetEntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimesheetController extends Controller
{
    public function __construct(
        private readonly TimesheetService $timesheetService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TimesheetEntryResource::collection(
            $this->timesheetService->list([
                'user_id' => $request->query('user_id'),
                'client_id' => $request->query('client_id'),
                'status' => $request->query('status'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'is_billable' => $request->query('is_billable') !== null
                    ? filter_var($request->query('is_billable'), FILTER_VALIDATE_BOOLEAN)
                    : null,
                'search' => $request->query('search'),
                'per_page' => min((int) ($request->query('per_page', 15)), 100),
            ]),
        );
    }

    public function store(StoreTimesheetEntryRequest $request): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timesheetService->create($request->validated()),
        );
    }

    public function show(TimesheetEntry $timesheet): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $timesheet->load(['user', 'client', 'approver']),
        );
    }

    public function update(UpdateTimesheetEntryRequest $request, TimesheetEntry $timesheet): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timesheetService->update($timesheet, $request->validated()),
        );
    }

    public function destroy(TimesheetEntry $timesheet): JsonResponse
    {
        $this->timesheetService->delete($timesheet);

        return response()->json(['message' => 'Timesheet entry deleted successfully.']);
    }

    public function submit(TimesheetEntry $timesheet): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timesheetService->submit($timesheet),
        );
    }

    public function approve(TimesheetEntry $timesheet): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timesheetService->approve($timesheet),
        );
    }

    public function reject(Request $request, TimesheetEntry $timesheet): TimesheetEntryResource
    {
        return new TimesheetEntryResource(
            $this->timesheetService->reject($timesheet, $request->input('reason')),
        );
    }

    public function bulkSubmit(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']]);

        $count = $this->timesheetService->bulkSubmit($request->input('ids'));

        return response()->json(['count' => $count]);
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']]);

        $count = $this->timesheetService->bulkApprove($request->input('ids'));

        return response()->json(['count' => $count]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->timesheetService->summary([
                'user_id' => $request->query('user_id'),
                'client_id' => $request->query('client_id'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'status' => $request->query('status'),
            ]),
        ]);
    }
}
