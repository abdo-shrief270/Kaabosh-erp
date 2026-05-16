<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use App\Domain\Engagement\Enums\EngagementStatus;
use App\Domain\Engagement\Enums\EngagementType;
use App\Domain\Engagement\Enums\WorkingPaperStatus;
use App\Domain\Engagement\Models\Engagement;
use App\Domain\Engagement\Models\WorkingPaper;
use App\Domain\Engagement\Services\EngagementService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    app()->instance('tenant.id', $this->tenant->id);

    $this->client = Client::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

// ──────────────────────────────────────
// Enum Labels
// ──────────────────────────────────────

describe('EngagementType labels', function (): void {
    it('returns correct labels for all engagement types', function (): void {
        expect(EngagementType::Audit->label())->toBe('Audit');
        expect(EngagementType::Review->label())->toBe('Review');
        expect(EngagementType::Compilation->label())->toBe('Compilation');
        expect(EngagementType::Tax->label())->toBe('Tax');
        expect(EngagementType::Bookkeeping->label())->toBe('Bookkeeping');
        expect(EngagementType::Consulting->label())->toBe('Consulting');
    });

    it('returns correct Arabic labels', function (): void {
        expect(EngagementType::Audit->labelAr())->toBe('مراجعة');
        expect(EngagementType::Tax->labelAr())->toBe('ضرائب');
    });
});

describe('EngagementStatus labels', function (): void {
    it('returns correct labels for all statuses', function (): void {
        expect(EngagementStatus::Planning->label())->toBe('Planning');
        expect(EngagementStatus::InProgress->label())->toBe('In Progress');
        expect(EngagementStatus::Review->label())->toBe('Review');
        expect(EngagementStatus::Completed->label())->toBe('Completed');
        expect(EngagementStatus::Archived->label())->toBe('Archived');
    });
});

describe('WorkingPaperStatus labels', function (): void {
    it('returns correct labels for all statuses', function (): void {
        expect(WorkingPaperStatus::NotStarted->label())->toBe('Not Started');
        expect(WorkingPaperStatus::InProgress->label())->toBe('In Progress');
        expect(WorkingPaperStatus::Completed->label())->toBe('Completed');
        expect(WorkingPaperStatus::Reviewed->label())->toBe('Reviewed');
    });
});

// ──────────────────────────────────────
// Progress Calculation
// ──────────────────────────────────────

describe('Progress calculation', function (): void {
    it('calculates progress as percentage of completed working papers', function (): void {
        $engagement = Engagement::query()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'engagement_type' => EngagementType::Audit,
            'name_ar' => 'مهمة اختبار',
            'status' => EngagementStatus::InProgress,
            'created_by' => $this->admin->id,
        ]);

        // Create 5 working papers: 3 completed (completed + reviewed count)
        foreach (range(1, 2) as $i) {
            WorkingPaper::query()->create([
                'tenant_id' => $this->tenant->id,
                'engagement_id' => $engagement->id,
                'section' => 'A-Planning',
                'title_ar' => "ورقة عمل {$i}",
                'status' => WorkingPaperStatus::Completed,
            ]);
        }

        WorkingPaper::query()->create([
            'tenant_id' => $this->tenant->id,
            'engagement_id' => $engagement->id,
            'section' => 'B-Execution',
            'title_ar' => 'ورقة عمل مراجعة',
            'status' => WorkingPaperStatus::Reviewed,
        ]);

        WorkingPaper::query()->create([
            'tenant_id' => $this->tenant->id,
            'engagement_id' => $engagement->id,
            'section' => 'B-Execution',
            'title_ar' => 'ورقة عمل قيد التنفيذ',
            'status' => WorkingPaperStatus::InProgress,
        ]);

        WorkingPaper::query()->create([
            'tenant_id' => $this->tenant->id,
            'engagement_id' => $engagement->id,
            'section' => 'C-Conclusion',
            'title_ar' => 'ورقة عمل لم تبدأ',
            'status' => WorkingPaperStatus::NotStarted,
        ]);

        // 3 out of 5 = 60%
        expect($engagement->progress())->toBe(60.0);
    });

    it('returns 0 when no working papers exist', function (): void {
        $engagement = Engagement::query()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'engagement_type' => EngagementType::Review,
            'name_ar' => 'مهمة فارغة',
        ]);

        expect($engagement->progress())->toBe(0.0);
    });
});

// ──────────────────────────────────────
// Deliverable Completion
// ──────────────────────────────────────

describe('Deliverable completion tracking', function (): void {
    it('marks a deliverable as completed with timestamp and user', function (): void {
        $engagement = Engagement::query()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'engagement_type' => EngagementType::Audit,
            'name_ar' => 'مهمة تسليمات',
        ]);

        $service = app(EngagementService::class);

        $deliverable = $service->addDeliverable($engagement, [
            'title_ar' => 'تقرير نهائي',
            'due_date' => '2026-06-30',
        ]);

        expect($deliverable->is_completed)->toBeFalse();

        $completed = $service->completeDeliverable($deliverable);

        expect($completed->is_completed)->toBeTrue();
        expect($completed->completed_at)->not->toBeNull();
        expect($completed->completed_by)->toBe($this->admin->id);
    });
});

// ──────────────────────────────────────
// Review Segregation
// ──────────────────────────────────────

describe('Working paper review segregation', function (): void {
    it('prevents reviewer from being the same user as the preparer', function (): void {
        $engagement = Engagement::query()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'engagement_type' => EngagementType::Audit,
            'name_ar' => 'مهمة فصل المهام',
        ]);

        $workingPaper = WorkingPaper::query()->create([
            'tenant_id' => $this->tenant->id,
            'engagement_id' => $engagement->id,
            'section' => 'A-Planning',
            'title_ar' => 'ورقة عمل',
            'status' => WorkingPaperStatus::Completed,
            'assigned_to' => $this->admin->id,
        ]);

        $service = app(EngagementService::class);

        // Current user (admin) is the assigned preparer — should fail
        expect(fn () => $service->reviewWorkingPaper($workingPaper))
            ->toThrow(ValidationException::class);
    });

    it('allows review by a different user than the preparer', function (): void {
        $engagement = Engagement::query()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'engagement_type' => EngagementType::Audit,
            'name_ar' => 'مهمة مراجعة',
        ]);

        $otherUser = createUser([
            'tenant_id' => $this->tenant->id,
        ]);

        $workingPaper = WorkingPaper::query()->create([
            'tenant_id' => $this->tenant->id,
            'engagement_id' => $engagement->id,
            'section' => 'A-Planning',
            'title_ar' => 'ورقة عمل',
            'status' => WorkingPaperStatus::Completed,
            'assigned_to' => $otherUser->id,
        ]);

        $service = app(EngagementService::class);
        $reviewed = $service->reviewWorkingPaper($workingPaper);

        expect($reviewed->status)->toBe(WorkingPaperStatus::Reviewed);
        expect($reviewed->reviewed_by)->toBe($this->admin->id);
        expect($reviewed->reviewed_at)->not->toBeNull();
    });
});
