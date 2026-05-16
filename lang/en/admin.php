<?php

declare(strict_types=1);

return [
    'brand' => 'Muhasebi · SuperAdmin',

    'nav_groups' => [
        'tenancy' => 'Tenancy',
        'billing' => 'Billing',
        'investors' => 'Investors',
        'content' => 'Content',
        'platform' => 'Platform',
    ],

    'resources' => [
        'tenant' => ['singular' => 'Tenant', 'plural' => 'Tenants'],
        'plan' => ['singular' => 'Plan', 'plural' => 'Plans'],
        'add_on' => ['singular' => 'Add-on', 'plural' => 'Add-ons'],
        'subscription' => ['singular' => 'Subscription', 'plural' => 'Subscriptions'],
        'subscription_payment' => ['singular' => 'Payment', 'plural' => 'Subscription Payments'],
        'currency' => ['singular' => 'Currency', 'plural' => 'Currencies'],
        'exchange_rate' => ['singular' => 'Exchange Rate', 'plural' => 'Exchange Rates'],
        'coupon' => ['singular' => 'Coupon', 'plural' => 'Coupons'],
        'investor' => ['singular' => 'Investor', 'plural' => 'Investors'],
        'profit_distribution' => ['singular' => 'Distribution', 'plural' => 'Profit Distributions'],
        'cms_page' => ['singular' => 'Page', 'plural' => 'CMS Pages'],
        'blog_post' => ['singular' => 'Post', 'plural' => 'Blog Posts'],
        'blog_category' => ['singular' => 'Category', 'plural' => 'Blog Categories'],
        'blog_tag' => ['singular' => 'Tag', 'plural' => 'Blog Tags'],
        'faq' => ['singular' => 'FAQ', 'plural' => 'FAQs'],
        'testimonial' => ['singular' => 'Testimonial', 'plural' => 'Testimonials'],
        'landing_setting' => ['singular' => 'Landing Setting', 'plural' => 'Landing Settings'],
        'slug_redirect' => ['singular' => 'Redirect', 'plural' => 'Slug Redirects'],
        'user' => ['singular' => 'User', 'plural' => 'Users'],
        'feature_flag' => ['singular' => 'Feature Flag', 'plural' => 'Feature Flags'],
        'audit_log' => ['singular' => 'Audit Log Entry', 'plural' => 'Audit Log'],
        'webhook_endpoint' => ['singular' => 'Webhook Endpoint', 'plural' => 'Webhook Endpoints'],
        'email_template' => ['singular' => 'Email Template', 'plural' => 'Email Templates'],
        'contact_submission' => ['singular' => 'Contact Submission', 'plural' => 'Contact Submissions'],
    ],

    'pages' => [
        'queue_monitor' => 'Queue Monitor',
        'two_factor_setup' => 'Two-Factor Setup',
    ],

    'topbar' => [
        'switch_language' => 'Switch language',
        'notifications' => 'Notifications',
        'mark_all_read' => 'Mark all read',
        'no_notifications' => 'No notifications yet',
    ],
];
