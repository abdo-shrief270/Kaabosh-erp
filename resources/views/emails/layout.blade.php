@php
/**
 * Tenant-aware email shell.
 *
 * Pass $brand from the Mailable's data array to render firm-to-customer
 * messages (invoices, portal invites, payment receipts) with the tenant's
 * brand instead of the platform default. Platform-to-firm messages
 * (welcome, trial expiring, usage threshold) just don't pass it and the
 * platform defaults below kick in.
 *
 * Resolve via TenantBrandingService::brandContext($tenant) on the backend.
 */
$brand = $brand ?? [
    'name'      => 'محاسبي',
    'primary'   => '#2c3e50',
    'secondary' => '#3498db',
    'logo_url'  => null,
];
$brandPrimary   = $brand['primary']   ?? '#2c3e50';
$brandSecondary = $brand['secondary'] ?? '#3498db';
$brandName      = $brand['name']      ?? 'محاسبي';
$brandLogo      = $brand['logo_url']  ?? null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $brandName }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            direction: rtl;
            text-align: right;
            line-height: 1.6;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .email-header {
            background-color: {{ $brandPrimary }};
            color: #ffffff;
            text-align: center;
            padding: 24px;
            border-radius: 8px 8px 0 0;
        }

        .email-header img.logo {
            max-height: 40px;
            max-width: 220px;
            object-fit: contain;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .email-body {
            background-color: #ffffff;
            padding: 32px 24px;
            border-left: 1px solid #e0e0e0;
            border-right: 1px solid #e0e0e0;
        }

        .email-body h2 {
            margin-top: 0;
            color: {{ $brandPrimary }};
            font-size: 20px;
        }

        .email-body p {
            margin: 12px 0;
            font-size: 15px;
            color: #555;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: {{ $brandPrimary }};
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            margin: 16px 0;
        }

        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
        }

        .info-box .label {
            font-size: 12px;
            color: #888;
            margin-bottom: 4px;
        }

        .info-box .value {
            font-size: 16px;
            font-weight: bold;
            color: {{ $brandPrimary }};
        }

        .email-footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px 24px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }

        .email-footer p {
            margin: 4px 0;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="logo">
            @else
                <h1>{{ $brandName }}</h1>
            @endif
        </div>

        <div class="email-body">
            @yield('content')
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ $brandName }}</p>
            <p>نظام المحاسبة السحابي لمكاتب المحاسبة المصرية</p>
        </div>
    </div>
</body>
</html>
