@extends('emails.layout')

@section('title', 'فترتك التجريبية تنتهي قريباً')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>فترتك التجريبية المجانية في <strong>{{ $tenantName }}</strong> تنتهي خلال <strong>{{ $daysLeft }} {{ $daysLeft === 1 ? 'يوم' : 'أيام' }}</strong>.</p>

    <p>نأمل أنك استمتعت بتجربة محاسبي! لمتابعة الاستفادة من جميع المزايا بدون انقطاع:</p>

    <div class="info-box">
        <div class="label">لماذا تختار محاسبي؟</div>
        <div class="value" style="font-size: 14px; line-height: 2;">
            ✓ فاتورة إلكترونية متوافقة مع ETA<br>
            ✓ تقارير مالية شاملة<br>
            ✓ إدارة عملاء متكاملة<br>
            ✓ دعم فني على مدار الساعة
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $upgradeUrl }}" class="btn">اختر خطتك الآن</a>
    </div>

    <p style="color: #888; font-size: 13px;">الأسعار تبدأ من 299 ج.م/شهر فقط. بدون التزام طويل المدى.</p>
@endsection
