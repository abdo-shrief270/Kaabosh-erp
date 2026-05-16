<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Onboarding\Services\OnboardingWizardService;
use App\Http\Controllers\Controller;
use App\Http\Resources\OnboardingProgressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingWizardController extends Controller
{
    public function __construct(
        private readonly OnboardingWizardService $wizardService,
    ) {}

    /**
     * Get current onboarding progress.
     */
    public function progress(): OnboardingProgressResource
    {
        $tenantId = (int) app('tenant.id');

        $progress = $this->wizardService->getProgress($tenantId);
        // getProgress uses firstOrCreate, so the first call returns a model
        // flagged wasRecentlyCreated=true — Laravel's ResourceResponse would
        // then emit 201. A GET should always be 200.
        $progress->wasRecentlyCreated = false;

        return new OnboardingProgressResource($progress);
    }

    /**
     * List available COA templates.
     */
    public function templates(): JsonResponse
    {
        $templates = $this->wizardService->getTemplates();

        return response()->json([
            'data' => $templates,
        ]);
    }

    /**
     * Select a COA template and create accounts for tenant.
     */
    public function selectTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => ['required', 'string', 'in:general,retail,services,manufacturing,construction,healthcare,technology'],
        ]);

        $tenantId = (int) app('tenant.id');
        $count = $this->wizardService->selectCoaTemplate($tenantId, $request->input('industry'));

        return response()->json([
            'message' => 'Chart of Accounts created successfully.',
            'accounts_created' => $count,
            'progress' => new OnboardingProgressResource(
                $this->wizardService->getProgress($tenantId),
            ),
        ]);
    }

    /**
     * Import opening balances via journal entry.
     */
    public function importBalances(Request $request): JsonResponse
    {
        $request->validate([
            'balances' => ['required', 'array', 'min:1'],
            'balances.*.account_code' => ['required', 'string'],
            'balances.*.debit' => ['required', 'numeric', 'min:0'],
            'balances.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        $tenantId = (int) app('tenant.id');
        $journalEntry = $this->wizardService->importOpeningBalances(
            $tenantId,
            $request->input('balances'),
        );

        return response()->json([
            'message' => 'Opening balances imported successfully.',
            'journal_entry_id' => $journalEntry->id,
            'entry_number' => $journalEntry->entry_number,
            'progress' => new OnboardingProgressResource(
                $this->wizardService->getProgress($tenantId),
            ),
        ]);
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(Request $request): OnboardingProgressResource
    {
        $request->validate([
            'step' => ['required', 'string'],
        ]);

        $tenantId = (int) app('tenant.id');
        $progress = $this->wizardService->completeStep($tenantId, $request->input('step'));
        $progress->wasRecentlyCreated = false;

        return new OnboardingProgressResource($progress);
    }

    /**
     * Skip a step.
     */
    public function skipStep(Request $request): OnboardingProgressResource
    {
        $request->validate([
            'step' => ['required', 'string'],
        ]);

        $tenantId = (int) app('tenant.id');
        $progress = $this->wizardService->skipStep($tenantId, $request->input('step'));
        $progress->wasRecentlyCreated = false;

        return new OnboardingProgressResource($progress);
    }
}
