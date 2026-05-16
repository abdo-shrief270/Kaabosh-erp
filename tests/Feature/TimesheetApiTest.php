<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use App\Domain\TimeTracking\Enums\TimesheetStatus;
use App\Domain\TimeTracking\Models\TimesheetEntry;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('GET /api/v1/timesheets', function (): void {

    it('lists timesheet entries', function (): void {
        TimesheetEntry::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/timesheets');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filters by status', function (): void {
        TimesheetEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'status' => TimesheetStatus::Draft,
        ]);
        TimesheetEntry::factory()->submitted()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/timesheets?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/timesheets', function (): void {

    it('creates a timesheet entry', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/timesheets', [
                'client_id' => $this->client->id,
                'date' => '2026-04-01',
                'task_description' => 'مراجعة قوائم مالية',
                'hours' => 4.5,
                'is_billable' => true,
                'hourly_rate' => 200,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.task_description', 'مراجعة قوائم مالية')
            ->assertJsonPath('data.hours', '4.50')
            ->assertJsonPath('data.status', 'draft');
    });

    it('validates required fields', function (): void {
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/timesheets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'task_description', 'hours']);
    });
});

describe('POST /api/v1/timesheets/{entry}/submit', function (): void {

    it('submits a draft entry', function (): void {
        $entry = TimesheetEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/timesheets/{$entry->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.status', 'submitted');
    });
});

describe('POST /api/v1/timesheets/{entry}/approve', function (): void {

    it('approves a submitted entry', function (): void {
        $entry = TimesheetEntry::factory()->submitted()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/timesheets/{$entry->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');
    });

    it('rejects approving a draft entry', function (): void {
        $entry = TimesheetEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'status' => TimesheetStatus::Draft,
        ]);

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/timesheets/{$entry->id}/approve")
            ->assertUnprocessable();
    });
});

describe('POST /api/v1/timesheets/{entry}/reject', function (): void {

    it('rejects a submitted entry with reason', function (): void {
        $entry = TimesheetEntry::factory()->submitted()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/timesheets/{$entry->id}/reject", [
                'reason' => 'ساعات غير صحيحة',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    });
});

describe('GET /api/v1/timesheets/summary', function (): void {

    it('returns hours summary', function (): void {
        TimesheetEntry::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'client_id' => $this->client->id,
            'hours' => 4,
            'is_billable' => true,
        ]);

        TimesheetEntry::factory()->nonBillable()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'hours' => 2,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/timesheets/summary');

        $response->assertOk()
            ->assertJsonPath('data.total_hours', 10)
            ->assertJsonPath('data.billable_hours', 8)
            ->assertJsonPath('data.non_billable_hours', 2);
    });
});
