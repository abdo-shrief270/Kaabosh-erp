<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Enums\FirmSubscriptionTier;
use App\Domain\Subscription\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Firm-level Plan rows. Each FirmSubscriptionTier enum case gets a Plan
 * with its feature manifest + limits stored on the `plans` table.
 *
 * Idempotent — uses updateOrCreate by slug.
 */
class FirmPlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'firm_free_trial'], [
            'name_en'        => 'Firm Free Trial',
            'name_ar'        => 'تجربة مجانية للمكتب',
            'description_en' => '14-day trial for accounting firms',
            'description_ar' => 'تجربة ١٤ يوم للمكاتب المحاسبية',
            'price_monthly'  => 0,
            'price_annual'   => 0,
            'currency'       => 'EGP',
            'trial_days'     => 14,
            'limits'         => $this->limitsFor(FirmSubscriptionTier::Starter),
            'features'       => FirmSubscriptionTier::Starter->features(),
            'is_active'      => true,
            'sort_order'     => 1,
        ]);

        foreach (FirmSubscriptionTier::cases() as $tier) {
            Plan::updateOrCreate(['slug' => $tier->value], [
                'name_en'        => $tier->label(),
                'name_ar'        => $this->labelAr($tier),
                'description_en' => $this->descriptionEn($tier),
                'description_ar' => $this->descriptionAr($tier),
                'price_monthly'  => $tier->monthlyPriceEgp() ?? 0,
                'price_annual'   => $tier->monthlyPriceEgp() ? ($tier->monthlyPriceEgp() * 12) : 0,
                'currency'       => 'EGP',
                'trial_days'     => 0,
                'limits'         => $this->limitsFor($tier),
                'features'       => $tier->features(),
                'is_active'      => true,
                'sort_order'     => match ($tier) {
                    FirmSubscriptionTier::Starter    => 2,
                    FirmSubscriptionTier::Pro        => 3,
                    FirmSubscriptionTier::Enterprise => 4,
                },
            ]);
        }
    }

    private function limitsFor(FirmSubscriptionTier $tier): array
    {
        return [
            'max_staff_seats'   => $tier->staffSeats(),
            'max_client_tenants'=> $tier->clientTenantCap(),
        ];
    }

    private function labelAr(FirmSubscriptionTier $tier): string
    {
        return match ($tier) {
            FirmSubscriptionTier::Starter    => 'باقة المكتب المبتدئة',
            FirmSubscriptionTier::Pro        => 'باقة المكتب المحترفة',
            FirmSubscriptionTier::Enterprise => 'باقة المكتب للشركات',
        };
    }

    private function descriptionEn(FirmSubscriptionTier $tier): string
    {
        return match ($tier) {
            FirmSubscriptionTier::Starter    => 'Core accounting tools for small firms — 5 staff, 10 clients',
            FirmSubscriptionTier::Pro        => 'Growing firms — 15 staff, 50 clients, payroll, e-invoice, advanced reports',
            FirmSubscriptionTier::Enterprise => 'Full breadth for large firms — unlimited staff & clients, API access, white-label',
        };
    }

    private function descriptionAr(FirmSubscriptionTier $tier): string
    {
        return match ($tier) {
            FirmSubscriptionTier::Starter    => 'أدوات المحاسبة الأساسية للمكاتب الصغيرة — ٥ موظفين، ١٠ عملاء',
            FirmSubscriptionTier::Pro        => 'للمكاتب المتنامية — ١٥ موظف، ٥٠ عميل، رواتب، فوترة إلكترونية، تقارير متقدّمة',
            FirmSubscriptionTier::Enterprise => 'عرض كامل للمكاتب الكبيرة — عدد غير محدود من الموظفين والعملاء، وصول API',
        };
    }
}
