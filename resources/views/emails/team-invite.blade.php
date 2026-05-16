@extends('emails.layout')

@section('title', 'دعوة للانضمام إلى الفريق')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>قام <strong>{{ $inviterName }}</strong> بدعوتك للانضمام إلى فريق العمل على نظام محاسبي.</p>

    <div class="info-box">
        <div class="label">بيانات الحساب</div>
        <div class="value" style="font-size: 14px; line-height: 2;">
            البريد الإلكتروني: {{ $userEmail }}<br>
            الدور: {{ $userRole }}
        </div>
    </div>

    <p>تم إنشاء حسابك بالفعل. يرجى تسجيل الدخول وتغيير كلمة المرور الخاصة بك.</p>

    <div style="text-align: center;">
        <a href="{{ $actionUrl }}" class="btn">تسجيل الدخول</a>
    </div>
@endsection
