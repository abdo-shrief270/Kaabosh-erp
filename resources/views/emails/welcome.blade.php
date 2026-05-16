@extends('emails.layout')

@section('title', 'مرحباً بك في محاسبي')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>تم إنشاء حسابك بنجاح في <strong>{{ $tenantName }}</strong>.</p>

    <p>يمكنك الآن البدء في إعداد شركتك واستخدام نظام محاسبي لإدارة أعمالك المحاسبية.</p>

    <div class="info-box">
        <div class="label">خطواتك التالية:</div>
        <div class="value" style="font-size: 14px; line-height: 2;">
            1. أكمل بيانات الشركة<br>
            2. اختر دليل الحسابات المناسب<br>
            3. أضف أول عميل<br>
            4. أصدر أول فاتورة
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $actionUrl }}" class="btn">ابدأ الإعداد الآن</a>
    </div>

    <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
@endsection
