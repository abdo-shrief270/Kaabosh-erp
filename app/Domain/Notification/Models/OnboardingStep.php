<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('onboarding_steps')]
#[Fillable([
    'tenant_id',
    'company_details_completed',
    'coa_template_selected',
    'coa_template_name',
    'first_client_added',
    'first_invoice_created',
    'team_invited',
    'sample_data_loaded',
    'wizard_completed',
    'wizard_completed_at',
    'wizard_skipped',
    'current_step',
])]
class OnboardingStep extends Model
{
    use BelongsToTenant;

    /** @var array<string, mixed> */
    protected $attributes = [
        'current_step' => 1,
        'company_details_completed' => false,
        'coa_template_selected' => false,
        'first_client_added' => false,
        'first_invoice_created' => false,
        'team_invited' => false,
        'sample_data_loaded' => false,
        'wizard_completed' => false,
        'wizard_skipped' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'company_details_completed' => 'boolean',
            'coa_template_selected' => 'boolean',
            'first_client_added' => 'boolean',
            'first_invoice_created' => 'boolean',
            'team_invited' => 'boolean',
            'sample_data_loaded' => 'boolean',
            'wizard_completed' => 'boolean',
            'wizard_skipped' => 'boolean',
            'wizard_completed_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    /** @var list<string> */
    /**
     * Steps that count toward the 100% wizard completion bar shown on the
     * dashboard. Must mirror the 4-step flow walked by the SPA's
     * /onboarding page: company → CoA → first client → team invite. The
     * `first_invoice_created` and `sample_data_loaded` flags still exist on
     * the record (auto-detected by getProgress()) but are not counted here
     * — adding an invoice has its own happy path elsewhere in the app and
     * dragging it into onboarding caused the banner to stick at 80% even
     * after the user finished every step the SPA actually presents.
     */
    private const STEPS = [
        'company_details_completed',
        'coa_template_selected',
        'first_client_added',
        'team_invited',
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function completionPercent(): int
    {
        $completed = 0;

        foreach (self::STEPS as $step) {
            if ($this->{$step}) {
                $completed++;
            }
        }

        return (int) round(($completed / count(self::STEPS)) * 100);
    }

    public function isComplete(): bool
    {
        return (bool) $this->wizard_completed;
    }

    public function nextStep(): int
    {
        return $this->current_step;
    }

    public function completeStep(string $step): void
    {
        if (! in_array($step, self::STEPS, true)) {
            return;
        }

        $this->{$step} = true;

        $index = array_search($step, self::STEPS, true);
        $nextStep = min($index + 2, count(self::STEPS));

        if ($this->current_step <= $index + 1) {
            $this->current_step = $nextStep;
        }

        // Check if all steps are completed
        $allCompleted = true;
        foreach (self::STEPS as $s) {
            if (! $this->{$s}) {
                $allCompleted = false;
                break;
            }
        }

        if ($allCompleted) {
            $this->wizard_completed = true;
            $this->wizard_completed_at = now();
        }

        $this->save();
    }
}
