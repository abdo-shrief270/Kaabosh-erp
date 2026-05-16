<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Onboarding\Services\OnboardingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\InviteTeamMemberRequest;
use App\Http\Requests\Onboarding\SetupCoaRequest;
use App\Http\Resources\OnboardingStepResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    public function progress(): OnboardingStepResource
    {
        return new OnboardingStepResource(
            $this->onboardingService->getProgress(),
        );
    }

    public function completeStep(Request $request): OnboardingStepResource
    {
        $request->validate([
            'step' => ['required', 'string'],
        ]);

        $step = $this->onboardingService->completeStep($request->input('step'));

        return new OnboardingStepResource($step);
    }

    public function skip(): OnboardingStepResource
    {
        return new OnboardingStepResource(
            $this->onboardingService->skipWizard(),
        );
    }

    public function setupCoa(SetupCoaRequest $request): OnboardingStepResource
    {
        $this->onboardingService->setupCoaTemplate($request->validated('template'));

        return new OnboardingStepResource(
            $this->onboardingService->getProgress(),
        );
    }

    public function setupFiscalYear(): JsonResponse
    {
        $this->onboardingService->setupFiscalYear();

        return response()->json([
            'message' => 'Fiscal year created successfully.',
        ]);
    }

    public function loadSampleData(): OnboardingStepResource
    {
        $this->onboardingService->loadSampleData();

        return new OnboardingStepResource(
            $this->onboardingService->getProgress(),
        );
    }

    public function inviteTeamMember(InviteTeamMemberRequest $request): JsonResponse
    {
        $user = $this->onboardingService->inviteTeamMember(
            email: $request->validated('email'),
            name: $request->validated('name'),
            role: $request->validated('role'),
        );

        return response()->json([
            'message' => 'Team member invited successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ], Response::HTTP_CREATED);
    }
}
