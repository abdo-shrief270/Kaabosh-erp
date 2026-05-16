<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use App\Domain\TimeTracking\Models\Timer;
use App\Domain\TimeTracking\Models\TimesheetEntry;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('POST /api/v1/timers/start', function (): void {

    it('starts a new timer', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/timers/start', [
                'client_id' => $this->client->id,
                'task_description' => 'مراجعة حسابات',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.task_description', 'مراجعة حسابات')
            ->assertJsonPath('data.is_running', true);
    });

    it('auto-stops previous timer when starting new one', function (): void {
        // Start first timer
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/timers/start', [
                'task_description' => 'مهمة أولى',
            ]);

        // Start second timer
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/timers/start', [
                'task_description' => 'مهمة ثانية',
            ]);

        // Only one running timer
        expect(Timer::query()->where('is_running', true)->count())->toBe(1);
        // First timer created a timesheet entry
        expect(TimesheetEntry::query()->count())->toBe(1);
    });
});

describe('POST /api/v1/timers/{timer}/stop', function (): void {

    it('stops a timer and creates a timesheet entry', function (): void {
        $timer = Timer::query()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'client_id' => $this->client->id,
            'task_description' => 'عمل تجريبي',
            'started_at' => now()->subHours(2),
            'is_running' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/timers/{$timer->id}/stop");

        $response->assertCreated()
            ->assertJsonPath('data.task_description', 'عمل تجريبي')
            ->assertJsonPath('data.status', 'draft');

        $timer->refresh();
        expect($timer->is_running)->toBeFalse();
        expect($timer->stopped_at)->not->toBeNull();

        expect(TimesheetEntry::query()->count())->toBe(1);
    });
});

describe('GET /api/v1/timers/current', function (): void {

    it('returns current running timer', function (): void {
        Timer::query()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'task_description' => 'مهمة جارية',
            'started_at' => now()->subMinutes(30),
            'is_running' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/timers/current');

        $response->assertOk()
            ->assertJsonPath('data.task_description', 'مهمة جارية')
            ->assertJsonPath('data.is_running', true);
    });

    it('returns null when no timer running', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/timers/current');

        $response->assertOk()
            ->assertJsonPath('data', null);
    });
});

describe('DELETE /api/v1/timers/{timer}', function (): void {

    it('discards a timer without creating entry', function (): void {
        $timer = Timer::query()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'task_description' => 'ملغاة',
            'started_at' => now()->subMinutes(10),
            'is_running' => true,
        ]);

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/timers/{$timer->id}")
            ->assertOk();

        expect(Timer::query()->find($timer->id))->toBeNull();
        expect(TimesheetEntry::query()->count())->toBe(0);
    });
});
