<?php

declare(strict_types=1);

/**
 * Feature catalog for plan-based gating.
 *
 * Each key is a feature slug used by Plan::hasFeature() and the `feature:` middleware.
 * 'category' distinguishes module-level features (gate whole route groups, e.g. Clients, Invoicing)
 * from addon features (fine-grained toggles like e_invoice, api_access).
 *
 * Route-level usage:
 *   Route::middleware('feature:clients')->group(...);
 *
 * Plan-level storage: Plan.features JSON column — { "clients": true, "e_invoice": false, ... }
 *
 * As of 2026-04-26: every staff-nav leaf has a `feature` flag for symmetry with
 * its `permission` flag. Truly core features (dashboard, notifications, basic
 * settings, etc.) are bundled into EVERY plan including `free_trial` so they're
 * effectively always-on; premium add-ons (webhooks, alerts, landing_page,
 * etc.) are bundled into `professional` and `enterprise` only.
 */
return [

    'catalog' => [
        // ─── Core modules ─────────────────────────────────────
        'clients' => [
            'name_en' => 'Clients',
            'name_ar' => 'العملاء',
            'category' => 'module',
            'group' => 'core',
        ],
        'documents' => [
            'name_en' => 'Documents',
            'name_ar' => 'المستندات',
            'category' => 'module',
            'group' => 'core',
        ],
        'invoicing' => [
            'name_en' => 'Invoicing',
            'name_ar' => 'الفواتير',
            'category' => 'module',
            'group' => 'core',
        ],
        'accounting' => [
            'name_en' => 'Accounting (COA / Journal)',
            'name_ar' => 'المحاسبة (دليل الحسابات / القيود)',
            'category' => 'module',
            'group' => 'core',
        ],
        'reports' => [
            'name_en' => 'Reports',
            'name_ar' => 'التقارير',
            'category' => 'module',
            'group' => 'core',
        ],

        // Always-on platform fundamentals — bundled into every plan including
        // free_trial. Listed as features for catalog symmetry with the staff
        // nav (every leaf has a `feature`), not because tenants would ever
        // realistically disable them.
        'dashboard' => [
            'name_en' => 'Dashboard',
            'name_ar' => 'لوحة التحكم',
            'category' => 'module',
            'group' => 'core',
        ],
        'notifications' => [
            'name_en' => 'Notifications',
            'name_ar' => 'الإشعارات',
            'category' => 'module',
            'group' => 'core',
        ],
        'activity_feed' => [
            'name_en' => 'Activity Feed',
            'name_ar' => 'سجل النشاط',
            'category' => 'module',
            'group' => 'core',
        ],
        'team_management' => [
            'name_en' => 'Team Management',
            'name_ar' => 'إدارة الفريق',
            'category' => 'module',
            'group' => 'core',
        ],
        'onboarding' => [
            'name_en' => 'Onboarding Wizard',
            'name_ar' => 'مساعد الإعداد',
            'category' => 'module',
            'group' => 'core',
        ],
        'subscription_management' => [
            'name_en' => 'Subscription & Billing',
            'name_ar' => 'الاشتراك والفوترة',
            'category' => 'module',
            'group' => 'core',
        ],
        'company_settings' => [
            'name_en' => 'Company Settings',
            'name_ar' => 'إعدادات المنشأة',
            'category' => 'module',
            'group' => 'core',
        ],
        'currencies' => [
            'name_en' => 'Currencies',
            'name_ar' => 'العملات',
            'category' => 'module',
            'group' => 'core',
        ],
        'general_settings' => [
            'name_en' => 'General Settings',
            'name_ar' => 'الإعدادات العامة',
            'category' => 'module',
            'group' => 'core',
        ],

        // ─── Finance modules ──────────────────────────────────
        'banking' => [
            'name_en' => 'Banking & Reconciliation',
            'name_ar' => 'البنوك والتسويات',
            'category' => 'module',
            'group' => 'finance',
        ],
        'expenses' => [
            'name_en' => 'Expenses',
            'name_ar' => 'المصروفات',
            'category' => 'module',
            'group' => 'finance',
        ],
        'bills_vendors' => [
            'name_en' => 'Bills & Vendors',
            'name_ar' => 'الفواتير والموردون',
            'category' => 'module',
            'group' => 'finance',
        ],
        'collections' => [
            'name_en' => 'Collections (AR)',
            'name_ar' => 'التحصيل',
            'category' => 'module',
            'group' => 'finance',
        ],
        'fixed_assets' => [
            'name_en' => 'Fixed Assets',
            'name_ar' => 'الأصول الثابتة',
            'category' => 'module',
            'group' => 'finance',
        ],
        'tax' => [
            'name_en' => 'Tax Management',
            'name_ar' => 'إدارة الضرائب',
            'category' => 'module',
            'group' => 'finance',
        ],
        'cost_centers' => [
            'name_en' => 'Cost Centers',
            'name_ar' => 'مراكز التكلفة',
            'category' => 'module',
            'group' => 'finance',
        ],
        'budgeting' => [
            'name_en' => 'Budgeting',
            'name_ar' => 'الموازنات',
            'category' => 'module',
            'group' => 'finance',
        ],

        // ─── Operations ───────────────────────────────────────
        'inventory' => [
            'name_en' => 'Inventory',
            'name_ar' => 'المخزون',
            'category' => 'module',
            'group' => 'operations',
        ],
        'payroll' => [
            'name_en' => 'Payroll',
            'name_ar' => 'الرواتب',
            'category' => 'module',
            'group' => 'operations',
        ],
        'timesheets' => [
            'name_en' => 'Timesheets',
            'name_ar' => 'سجلات الوقت',
            'category' => 'module',
            'group' => 'operations',
        ],
        'engagements' => [
            'name_en' => 'Engagements',
            'name_ar' => 'الارتباطات',
            'category' => 'module',
            'group' => 'operations',
        ],
        'ecommerce' => [
            'name_en' => 'E-Commerce Integration',
            'name_ar' => 'تكامل التجارة الإلكترونية',
            'category' => 'module',
            'group' => 'operations',
        ],

        // ─── Workflows ────────────────────────────────────────
        'approvals' => [
            'name_en' => 'Approvals',
            'name_ar' => 'الاعتمادات',
            'category' => 'addon',
            'group' => 'workflows',
        ],
        'alerts' => [
            'name_en' => 'Alerts',
            'name_ar' => 'التنبيهات',
            'category' => 'addon',
            'group' => 'workflows',
        ],

        // ─── Add-ons / integrations ───────────────────────────
        'e_invoice' => [
            'name_en' => 'ETA E-Invoice',
            'name_ar' => 'الفاتورة الإلكترونية (ETA)',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'api_access' => [
            'name_en' => 'Public API Access',
            'name_ar' => 'الوصول إلى الـ API',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'custom_reports' => [
            'name_en' => 'Custom Reports',
            'name_ar' => 'تقارير مخصصة',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'client_portal' => [
            'name_en' => 'Client Portal',
            'name_ar' => 'بوابة العملاء',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'client_messaging' => [
            'name_en' => 'Client Messaging',
            'name_ar' => 'مراسلات العملاء',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'data_import' => [
            'name_en' => 'Data Import (CSV)',
            'name_ar' => 'استيراد البيانات (CSV)',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'webhooks' => [
            'name_en' => 'Webhooks',
            'name_ar' => 'الـ Webhooks',
            'category' => 'addon',
            'group' => 'integrations',
        ],
        'landing_page' => [
            'name_en' => 'Public Landing Page',
            'name_ar' => 'صفحة الهبوط العامة',
            'category' => 'addon',
            'group' => 'integrations',
        ],

        // ─── Compliance / support ────────────────────────────
        'priority_support' => [
            'name_en' => 'Priority Support',
            'name_ar' => 'الدعم المميز',
            'category' => 'addon',
            'group' => 'support',
        ],
        'audit_log' => [
            'name_en' => 'Audit Log (Compliance)',
            'name_ar' => 'سجل المراجعة (الامتثال)',
            'category' => 'addon',
            'group' => 'compliance',
        ],

        // ─── Experimental ────────────────────────────────────
        // Off by default for every plan; enabled per-tenant via the admin
        // feature_flags table when a tenant opts into the AI preview.
        'experimental_ai' => [
            'name_en' => 'AI Suggestions (Preview)',
            'name_ar' => 'الاقتراحات بالذكاء الاصطناعي (تجريبي)',
            'category' => 'experimental',
            'group' => 'experimental',
        ],
    ],

    /**
     * Default feature bundles per plan slug.
     * Used by PlanSeeder to populate the plans.features JSON column.
     *
     * `core_always_on` is included by reference in every plan's bundle so a new
     * "always available" feature only needs to be added in one place.
     */
    'plan_bundles' => [
        // Reusable: core platform features that should never be missing.
        // Spread into every plan below.
        '__core_always_on' => [
            'dashboard',
            'notifications',
            'activity_feed',
            'team_management',
            'onboarding',
            'subscription_management',
            'company_settings',
            'currencies',
            'general_settings',
        ],

        'free_trial' => [
            'dashboard', 'notifications', 'activity_feed',
            'team_management', 'onboarding', 'subscription_management',
            'company_settings', 'currencies', 'general_settings',
            'clients', 'documents',
        ],
        'starter' => [
            'dashboard', 'notifications', 'activity_feed',
            'team_management', 'onboarding', 'subscription_management',
            'company_settings', 'currencies', 'general_settings',
            'clients', 'documents', 'invoicing', 'accounting', 'reports',
            'expenses', 'custom_reports',
            'data_import',
        ],
        'professional' => [
            'dashboard', 'notifications', 'activity_feed',
            'team_management', 'onboarding', 'subscription_management',
            'company_settings', 'currencies', 'general_settings',
            'clients', 'documents', 'invoicing', 'accounting', 'reports',
            'banking', 'expenses', 'bills_vendors', 'collections',
            'fixed_assets', 'tax', 'cost_centers', 'budgeting',
            'inventory', 'payroll', 'timesheets',
            'engagements',
            'e_invoice', 'api_access', 'custom_reports', 'client_portal',
            'client_messaging',
            'data_import',
            'approvals', 'alerts',
            'landing_page',
        ],
        'enterprise' => [
            'dashboard', 'notifications', 'activity_feed',
            'team_management', 'onboarding', 'subscription_management',
            'company_settings', 'currencies', 'general_settings',
            'clients', 'documents', 'invoicing', 'accounting', 'reports',
            'banking', 'expenses', 'bills_vendors', 'collections',
            'fixed_assets', 'tax', 'cost_centers', 'budgeting',
            'inventory', 'payroll', 'timesheets', 'ecommerce',
            'engagements',
            'e_invoice', 'api_access', 'custom_reports', 'client_portal',
            'client_messaging',
            'data_import',
            'approvals', 'alerts',
            'landing_page',
            'webhooks',
            'priority_support', 'audit_log',
            'experimental_ai',
        ],
    ],
];
