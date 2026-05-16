<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum FirmSubscriptionTier: string
{
    case Starter    = 'firm_starter';
    case Pro        = 'firm_pro';
    case Enterprise = 'firm_enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Starter    => 'Firm Starter',
            self::Pro        => 'Firm Pro',
            self::Enterprise => 'Firm Enterprise',
        };
    }

    /**
     * Monthly base price in EGP. Enterprise is null (custom quote).
     */
    public function monthlyPriceEgp(): ?int
    {
        return match ($this) {
            self::Starter    => 1_999,
            self::Pro        => 3_999,
            self::Enterprise => null,
        };
    }

    /**
     * Maximum number of firm staff seats included in the base subscription.
     * Enterprise is unlimited (returns null).
     */
    public function staffSeats(): ?int
    {
        return match ($this) {
            self::Starter    => 5,
            self::Pro        => 15,
            self::Enterprise => null,
        };
    }

    /**
     * Maximum active client-tenants the firm can manage. Enterprise is
     * unlimited (returns null).
     */
    public function clientTenantCap(): ?int
    {
        return match ($this) {
            self::Starter    => 10,
            self::Pro        => 50,
            self::Enterprise => null,
        };
    }

    /**
     * Feature manifest per plan. Drives FeatureFlagService — every catalog
     * key in this list is enabled for the firm's entire tenant set; keys
     * not listed are disabled.
     *
     * Higher tiers include all lower-tier features by virtue of array merge.
     *
     * @return array<string>
     */
    public function features(): array
    {
        $starter = [
            // Core operating tools every accounting firm needs from day one.
            'dashboard', 'notifications', 'activity_feed', 'audit_log',
            'clients', 'documents', 'invoicing', 'collections', 'client_portal',
            'bills_vendors', 'expenses',
            'banking', 'currencies',
            'accounting', 'reports',
            'team_management', 'onboarding', 'subscription_management',
            'company_settings', 'general_settings',
        ];

        $pro = array_merge($starter, [
            // Adds the productivity + compliance modules a growing firm needs.
            'payroll', 'timesheets', 'engagements',
            'inventory', 'fixed_assets',
            'tax', 'e_invoice',
            'budgeting', 'approvals', 'alerts',
            'custom_reports', 'webhooks',
            'task_board',
        ]);

        $enterprise = array_merge($pro, [
            // Enterprise — full breadth for large firms and bespoke setups.
            'cost_centers', 'ecommerce',
            'client_messaging', 'data_import',
            'api_access', 'landing_page',
            'priority_support', 'experimental_ai',
        ]);

        return match ($this) {
            self::Starter    => $starter,
            self::Pro        => $pro,
            self::Enterprise => $enterprise,
        };
    }
}
