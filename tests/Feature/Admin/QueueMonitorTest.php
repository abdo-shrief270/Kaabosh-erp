<?php

declare(strict_types=1);

use App\Domain\Shared\Models\FailedJob;
use App\Filament\Admin\Pages\QueueMonitor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
});

describe('QueueMonitor access control', function (): void {

    it('allows SuperAdmin to view the page', function (): void {
        $this->actingAs($this->superAdmin);

        $this->get('/admin/queue-monitor')->assertOk();
    });

    it('denies access to non-SuperAdmin users', function (): void {
        $tenant = createTenant();
        $admin = createAdminUser($tenant);

        $this->actingAs($admin);

        $this->get('/admin/queue-monitor')->assertForbidden();
    });

    it('exposes canAccess gate consistently with role', function (): void {
        $this->actingAs($this->superAdmin);
        expect(QueueMonitor::canAccess())->toBeTrue();

        $tenant = createTenant();
        $this->actingAs(createAdminUser($tenant));
        expect(QueueMonitor::canAccess())->toBeFalse();
    });
});

describe('QueueMonitor table', function (): void {

    it('renders failed jobs seeded into the failed_jobs table', function (): void {
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-001',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\TestJob']),
            'exception' => 'RuntimeException: boom',
            'failed_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(QueueMonitor::class)
            ->assertCanSeeTableRecords(FailedJob::all());
    });

    it('retry action dispatches queue:retry with the record uuid', function (): void {
        DB::table('failed_jobs')->insert([
            'uuid' => 'retry-uuid-002',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Whoops',
            'failed_at' => now(),
        ]);

        $record = FailedJob::query()->where('uuid', 'retry-uuid-002')->firstOrFail();

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => ['retry-uuid-002']])
            ->andReturn(0);

        $this->actingAs($this->superAdmin);

        Livewire::test(QueueMonitor::class)
            ->callTableAction('retry', record: $record)
            ->assertHasNoTableActionErrors();
    });

    it('delete action dispatches queue:forget with the record uuid', function (): void {
        DB::table('failed_jobs')->insert([
            'uuid' => 'forget-uuid-003',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Boom',
            'failed_at' => now(),
        ]);

        $record = FailedJob::query()->where('uuid', 'forget-uuid-003')->firstOrFail();

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:forget', ['id' => 'forget-uuid-003'])
            ->andReturn(0);

        $this->actingAs($this->superAdmin);

        Livewire::test(QueueMonitor::class)
            ->callTableAction('delete', record: $record)
            ->assertHasNoTableActionErrors();
    });

    it('retry_all header action dispatches queue:retry with all', function (): void {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => ['all']])
            ->andReturn(0);

        $this->actingAs($this->superAdmin);

        Livewire::test(QueueMonitor::class)
            ->callAction('retry_all')
            ->assertHasNoActionErrors();
    });

    it('flush_all header action dispatches queue:flush', function (): void {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:flush')
            ->andReturn(0);

        $this->actingAs($this->superAdmin);

        Livewire::test(QueueMonitor::class)
            ->callAction('flush_all')
            ->assertHasNoActionErrors();
    });
});
