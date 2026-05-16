<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Domain\Subscription\Models\AddOn;
use Illuminate\Database\Seeder;

/**
 * Default add-on catalog.
 *
 * Boost rows: each row's `boost` JSON map keys MUST match the keys used by
 * Plan::limits (max_users, max_storage_bytes, …) — UsageService merges by
 * literal key match.
 *
 * Feature rows: `feature_slug` MUST match an entry in config/features.php
 * `catalog` so PlanFeatureCache picks them up.
 *
 * Credit-pack rows: `credit_kind` is a free-form string consumed by the
 * service that drains them (e.g. SmsService reads `sms`, the upcoming
 * AI feature will read `ai_tokens`).
 */
class AddOnSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ── Boost: extra seats ──
            [
                'slug' => 'extra_users_5',
                'name_en' => '+5 User Seats',
                'name_ar' => '+5 مستخدمين',
                'description_en' => 'Add five extra user seats to your plan, billed monthly.',
                'description_ar' => 'أضف خمسة مستخدمين إضافيين إلى باقتك، شهرياً.',
                'type' => AddOnType::Boost,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'boost' => ['max_users' => 5],
                'price_monthly' => 99,
                'price_annual' => 990,
                'sort_order' => 10,
            ],

            // ── Boost: extra storage ──
            [
                'slug' => 'extra_storage_5gb',
                'name_en' => '+5 GB Storage',
                'name_ar' => '+5 جيجابايت تخزين',
                'description_en' => 'Add five gigabytes of document storage to your plan.',
                'description_ar' => 'أضف خمسة جيجابايت من تخزين المستندات إلى باقتك.',
                'type' => AddOnType::Boost,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'boost' => ['max_storage_bytes' => 5368709120],
                'price_monthly' => 49,
                'price_annual' => 490,
                'sort_order' => 20,
            ],

            // ── Boost: extra invoices ──
            [
                'slug' => 'extra_invoices_200',
                'name_en' => '+200 Invoices / month',
                'name_ar' => '+200 فاتورة شهرياً',
                'description_en' => 'Issue an additional two hundred invoices each month.',
                'description_ar' => 'أصدر مئتي فاتورة إضافية شهرياً.',
                'type' => AddOnType::Boost,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'boost' => ['max_invoices_per_month' => 200],
                'price_monthly' => 79,
                'price_annual' => 790,
                'sort_order' => 30,
            ],

            // ── Boost: extra clients ──
            [
                'slug' => 'extra_clients_50',
                'name_en' => '+50 Clients',
                'name_ar' => '+50 عميل',
                'description_en' => 'Increase your client roster by fifty.',
                'description_ar' => 'زِد قائمة عملائك بخمسين.',
                'type' => AddOnType::Boost,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'boost' => ['max_clients' => 50],
                'price_monthly' => 59,
                'price_annual' => 590,
                'sort_order' => 40,
            ],

            // ── Feature: priority support ──
            [
                'slug' => 'priority_support_addon',
                'name_en' => 'Priority Support',
                'name_ar' => 'دعم فني متميز',
                'description_en' => 'Skip the queue with same-day response from senior support.',
                'description_ar' => 'استجابة في نفس اليوم من فريق الدعم الأقدم.',
                'type' => AddOnType::Feature,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'feature_slug' => 'priority_support',
                'price_monthly' => 199,
                'price_annual' => 1990,
                'sort_order' => 50,
            ],

            // ── Feature: white-label ──
            [
                'slug' => 'white_label_addon',
                'name_en' => 'White Label',
                'name_ar' => 'علامة بيضاء',
                'description_en' => 'Replace Muhasebi branding with your own on invoices and the client portal.',
                'description_ar' => 'استبدل علامة محاسبي بعلامتك على الفواتير وبوابة العملاء.',
                'type' => AddOnType::Feature,
                'billing_cycle' => AddOnBillingCycle::Monthly,
                'feature_slug' => 'white_label',
                'price_monthly' => 299,
                'price_annual' => 2990,
                'sort_order' => 60,
            ],

            // ── Credit pack: SMS ──
            [
                'slug' => 'sms_credits_1000',
                'name_en' => '1 000 SMS Credits',
                'name_ar' => '1000 رسالة SMS',
                'description_en' => 'One-time pack of one thousand SMS credits, valid for the current period.',
                'description_ar' => 'باقة لمرة واحدة بألف رصيد SMS، صالحة للفترة الحالية.',
                'type' => AddOnType::CreditPack,
                'billing_cycle' => AddOnBillingCycle::Once,
                'credit_kind' => 'sms',
                'credit_quantity' => 1000,
                'price_monthly' => 0,
                'price_annual' => 0,
                'price_once' => 149,
                'sort_order' => 70,
            ],

            // ── Credit pack: AI tokens (placeholder for the upcoming AI feature) ──
            [
                'slug' => 'ai_tokens_100k',
                'name_en' => '100 000 AI Tokens',
                'name_ar' => '100 ألف رصيد ذكاء اصطناعي',
                'description_en' => 'One-time pack of 100k AI tokens for journal-entry suggestions and anomaly explanations.',
                'description_ar' => 'باقة لمرة واحدة من 100 ألف رصيد للاقتراحات الذكية وشرح الشذوذ.',
                'type' => AddOnType::CreditPack,
                'billing_cycle' => AddOnBillingCycle::Once,
                'credit_kind' => 'ai_tokens',
                'credit_quantity' => 100000,
                'price_monthly' => 0,
                'price_annual' => 0,
                'price_once' => 99,
                'sort_order' => 80,
            ],
        ];

        foreach ($rows as $row) {
            $row['type'] = $row['type']->value;
            $row['billing_cycle'] = $row['billing_cycle']->value;
            AddOn::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
