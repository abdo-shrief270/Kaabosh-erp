<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Subscription\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = array_keys(config('features.catalog', []));
        $bundles = config('features.plan_bundles', []);

        $plans = [
            [
                'name_en' => 'Free Trial',
                'name_ar' => 'تجربة مجانية',
                'slug' => 'free_trial',
                'description_en' => 'Try Muhasebi free for 14 days with limited features.',
                'description_ar' => 'جرب محاسبي مجاناً لمدة 14 يوماً مع ميزات محدودة.',
                'price_monthly' => 0,
                'price_annual' => 0,
                'currency' => 'EGP',
                'trial_days' => 14,
                'limits' => [
                    'max_users' => 2,
                    'max_clients' => 10,
                    'max_storage_bytes' => 536870912,
                    'max_invoices_per_month' => 20,
                    'max_bills_per_month' => 20,
                    'max_journal_entries_per_month' => 100,
                    'max_bank_imports_per_month' => 5,
                    'max_documents' => 100,
                    'max_api_calls_per_month' => 0,
                ],
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name_en' => 'Starter',
                'name_ar' => 'أساسي',
                'slug' => 'starter',
                'description_en' => 'Perfect for freelancers and small businesses getting started.',
                'description_ar' => 'مثالي للمستقلين والشركات الصغيرة في بداياتها.',
                'price_monthly' => 499,
                'price_annual' => 4990,
                'currency' => 'EGP',
                'trial_days' => 14,
                'limits' => [
                    'max_users' => 5,
                    'max_clients' => 50,
                    'max_storage_bytes' => 2147483648,
                    'max_invoices_per_month' => 200,
                    'max_bills_per_month' => 200,
                    'max_journal_entries_per_month' => 1000,
                    'max_bank_imports_per_month' => 25,
                    'max_documents' => 1000,
                    'max_api_calls_per_month' => 5000,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Professional',
                'name_ar' => 'احترافي',
                'slug' => 'professional',
                'description_en' => 'Advanced features for growing businesses and teams.',
                'description_ar' => 'ميزات متقدمة للشركات والفرق المتنامية.',
                'price_monthly' => 1199,
                'price_annual' => 11990,
                'currency' => 'EGP',
                'trial_days' => 14,
                'limits' => [
                    'max_users' => 15,
                    'max_clients' => 200,
                    'max_storage_bytes' => 10737418240,
                    'max_invoices_per_month' => 1000,
                    'max_bills_per_month' => 1000,
                    'max_journal_entries_per_month' => 5000,
                    'max_bank_imports_per_month' => 100,
                    'max_documents' => 10000,
                    'max_api_calls_per_month' => 25000,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_en' => 'Enterprise',
                'name_ar' => 'مؤسسات',
                'slug' => 'enterprise',
                'description_en' => 'Full-featured plan for large organizations with priority support.',
                'description_ar' => 'خطة شاملة للمؤسسات الكبيرة مع دعم فني متميز.',
                'price_monthly' => 2999,
                'price_annual' => 29990,
                'currency' => 'EGP',
                'trial_days' => 30,
                'limits' => [
                    'max_users' => 100,
                    'max_clients' => -1,
                    'max_storage_bytes' => 53687091200,
                    'max_invoices_per_month' => -1,
                    'max_bills_per_month' => -1,
                    'max_journal_entries_per_month' => -1,
                    'max_bank_imports_per_month' => -1,
                    'max_documents' => -1,
                    'max_api_calls_per_month' => -1,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            $enabled = $bundles[$plan['slug']] ?? [];
            $features = [];
            foreach ($catalog as $key) {
                $features[$key] = in_array($key, $enabled, true);
            }
            $plan['features'] = $features;

            // Look up including soft-deleted so an admin-disabled plan gets
            // restored & re-priced instead of colliding on the unique slug.
            $existing = Plan::query()->withTrashed()->where('slug', $plan['slug'])->first();
            if ($existing) {
                $existing->restore();
                $existing->update($plan);
            } else {
                Plan::query()->create($plan);
            }
        }
    }
}
