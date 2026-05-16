<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Onboarding\Models\OnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnboardingProgress */
class OnboardingProgressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'current_step' => $this->current_step,
            'total_steps' => $this->total_steps,
            'completed_steps' => $this->completed_steps,
            'company_profile_completed' => $this->company_profile_completed,
            'coa_selected' => $this->coa_selected,
            'opening_balances_imported' => $this->opening_balances_imported,
            'team_invited' => $this->team_invited,
            'first_invoice_created' => $this->first_invoice_created,
            'eta_configured' => $this->eta_configured,
            'completed_at' => $this->completed_at?->toISOString(),
            'completion_percent' => $this->completionPercent(),
            'next_action' => $this->nextAction(),
            'is_complete' => $this->isComplete(),
        ];
    }
}
