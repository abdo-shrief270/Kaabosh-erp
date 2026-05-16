<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Notification\Models\OnboardingStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnboardingStep */
class OnboardingStepResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'company_details_completed' => $this->company_details_completed,
            'coa_template_selected' => $this->coa_template_selected,
            'coa_template_name' => $this->coa_template_name,
            'first_client_added' => $this->first_client_added,
            'first_invoice_created' => $this->first_invoice_created,
            'team_invited' => $this->team_invited,
            'sample_data_loaded' => $this->sample_data_loaded,
            'wizard_completed' => $this->wizard_completed,
            'wizard_completed_at' => $this->wizard_completed_at?->toISOString(),
            'wizard_skipped' => $this->wizard_skipped,
            'current_step' => $this->current_step,
            'completion_percent' => $this->completionPercent(),
        ];
    }
}
