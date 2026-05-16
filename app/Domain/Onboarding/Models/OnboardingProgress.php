<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('onboarding_progress')]
#[Fillable([
    'tenant_id',
    'current_step',
    'total_steps',
    'completed_steps',
    'company_profile_completed',
    'coa_selected',
    'opening_balances_imported',
    'team_invited',
    'first_invoice_created',
    'eta_configured',
    'completed_at',
])]
class OnboardingProgress extends Model
{
    use BelongsToTenant;

    /** @var array<string, mixed> */
    protected $attributes = [
        'current_step' => 1,
        'total_steps' => 7,
        'company_profile_completed' => false,
        'coa_selected' => false,
        'opening_balances_imported' => false,
        'team_invited' => false,
        'first_invoice_created' => false,
        'eta_configured' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'total_steps' => 'integer',
            'completed_steps' => 'array',
            'company_profile_completed' => 'boolean',
            'coa_selected' => 'boolean',
            'opening_balances_imported' => 'boolean',
            'team_invited' => 'boolean',
            'first_invoice_created' => 'boolean',
            'eta_configured' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /** @var list<string> */
    public const STEPS = [
        'company_profile',
        'coa_selection',
        'opening_balances',
        'team_invitation',
        'first_invoice',
        'eta_configuration',
        'review',
    ];

    /** @var array<string, string> */
    public const STEP_COLUMNS = [
        'company_profile' => 'company_profile_completed',
        'coa_selection' => 'coa_selected',
        'opening_balances' => 'opening_balances_imported',
        'team_invitation' => 'team_invited',
        'first_invoice' => 'first_invoice_created',
        'eta_configuration' => 'eta_configured',
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function completionPercent(): int
    {
        $completedSteps = is_array($this->completed_steps) ? $this->completed_steps : [];

        return (int) round((count($completedSteps) / $this->total_steps) * 100);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function nextAction(): string
    {
        foreach (self::STEPS as $index => $step) {
            $column = self::STEP_COLUMNS[$step] ?? null;
            if ($column && ! $this->{$column}) {
                return $step;
            }
        }

        return 'complete';
    }
}
