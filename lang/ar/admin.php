<?php

declare(strict_types=1);

return [
    'brand' => 'محاسبي · الإدارة العليا',

    'nav_groups' => [
        'tenancy' => 'المستأجرون',
        'billing' => 'الفوترة',
        'investors' => 'المستثمرون',
        'content' => 'المحتوى',
        'platform' => 'المنصة',
    ],

    'resources' => [
        'tenant' => ['singular' => 'مستأجر', 'plural' => 'المستأجرون'],
        'plan' => ['singular' => 'خطة', 'plural' => 'الخطط'],
        'add_on' => ['singular' => 'إضافة', 'plural' => 'الإضافات'],
        'subscription' => ['singular' => 'اشتراك', 'plural' => 'الاشتراكات'],
        'subscription_payment' => ['singular' => 'دفعة', 'plural' => 'مدفوعات الاشتراكات'],
        'currency' => ['singular' => 'عملة', 'plural' => 'العملات'],
        'exchange_rate' => ['singular' => 'سعر صرف', 'plural' => 'أسعار الصرف'],
        'coupon' => ['singular' => 'قسيمة', 'plural' => 'القسائم'],
        'investor' => ['singular' => 'مستثمر', 'plural' => 'المستثمرون'],
        'profit_distribution' => ['singular' => 'توزيع ربح', 'plural' => 'توزيعات الأرباح'],
        'cms_page' => ['singular' => 'صفحة', 'plural' => 'صفحات الموقع'],
        'blog_post' => ['singular' => 'مقالة', 'plural' => 'المقالات'],
        'blog_category' => ['singular' => 'تصنيف', 'plural' => 'تصنيفات المقالات'],
        'blog_tag' => ['singular' => 'وسم', 'plural' => 'وسوم المقالات'],
        'faq' => ['singular' => 'سؤال شائع', 'plural' => 'الأسئلة الشائعة'],
        'testimonial' => ['singular' => 'شهادة', 'plural' => 'شهادات العملاء'],
        'landing_setting' => ['singular' => 'إعداد صفحة هبوط', 'plural' => 'إعدادات صفحة الهبوط'],
        'slug_redirect' => ['singular' => 'إعادة توجيه', 'plural' => 'إعادة توجيه الروابط'],
        'user' => ['singular' => 'مستخدم', 'plural' => 'المستخدمون'],
        'feature_flag' => ['singular' => 'مفتاح ميزة', 'plural' => 'مفاتيح المزايا'],
        'audit_log' => ['singular' => 'سجل تدقيق', 'plural' => 'سجلات التدقيق'],
        'webhook_endpoint' => ['singular' => 'نقطة ويبهوك', 'plural' => 'نقاط الويبهوك'],
        'email_template' => ['singular' => 'قالب بريد', 'plural' => 'قوالب البريد'],
        'contact_submission' => ['singular' => 'رسالة تواصل', 'plural' => 'رسائل التواصل'],
    ],

    'pages' => [
        'queue_monitor' => 'مراقبة الطابور',
        'two_factor_setup' => 'إعداد المصادقة الثنائية',
    ],

    'topbar' => [
        'switch_language' => 'تبديل اللغة',
        'notifications' => 'الإشعارات',
        'mark_all_read' => 'تعليم الكل كمقروء',
        'no_notifications' => 'لا توجد إشعارات',
    ],
];
