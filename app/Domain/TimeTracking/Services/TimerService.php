<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Models\Timer;
use App\Domain\TimeTracking\Models\TimesheetEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TimerService
{
    public function __construct(
        private readonly TimesheetService $timesheetService,
    ) {}

    /**
     * Start a new timer. Auto-stops any running timer first.
     *
     * @param  array<string, mixed>  $data
     */
    public function start(array $data): Timer
    {
        $userId = (int) Auth::id();

        // Stop any running timer for this user
        $running = Timer::query()->forUser($userId)->running()->first();

        if ($running) {
            $this->stopTimer($running);
        }

        return Timer::query()->create([
            'tenant_id' => (int) app('tenant.id'),
            'user_id' => $userId,
            'client_id' => $data['client_id'] ?? null,
            'task_description' => $data['task_description'],
            'started_at' => now(),
            'is_running' => true,
        ]);
    }

    /**
     * Stop a timer and create a timesheet entry.
     *
     * @throws ValidationException
     */
    public function stop(Timer $timer): TimesheetEntry
    {
        if (! $timer->is_running) {
            throw ValidationException::withMessages([
                'timer' => [
                    'This timer is already stopped.',
                    'هذا المؤقت متوقف بالفعل.',
                ],
            ]);
        }

        return $this->stopTimer($timer);
    }

    /**
     * Get the current running timer for the authenticated user.
     */
    public function current(): ?Timer
    {
        return Timer::query()
            ->forUser((int) Auth::id())
            ->running()
            ->with('client')
            ->first();
    }

    /**
     * Discard a timer without creating an entry.
     */
    public function discard(Timer $timer): void
    {
        $timer->delete();
    }

    /**
     * Internal: stop a timer and create a timesheet entry.
     */
    private function stopTimer(Timer $timer): TimesheetEntry
    {
        $timer->update([
            'stopped_at' => now(),
            'is_running' => false,
        ]);

        $hours = $timer->elapsedHours();

        return $this->timesheetService->create([
            'user_id' => $timer->user_id,
            'client_id' => $timer->client_id,
            'date' => $timer->started_at->toDateString(),
            'task_description' => $timer->task_description,
            'hours' => max($hours, 0.01),
        ]);
    }
}
