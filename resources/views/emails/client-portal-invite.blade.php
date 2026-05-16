@extends('emails.layout')

@section('title', 'دعوة لبوابة العملاء')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>تمت دعوتك للوصول إلى بوابة العملاء الخاصة بـ <strong>{{ $tenantName }}</strong>.</p>

    <p>من خلال البوابة يمكنك:</p>

    <div class="info-box">
        <div class="value" style="font-size: 14px; line-height: 2;">
            - عرض الفواتير وحالة الدفع<br>
            - الدفع الإلكتروني<br>
            - تحميل ومشاركة المستندات<br>
            - التواصل مع مكتب المحاسبة
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $actionUrl }}" class="btn">الدخول إلى البوابة</a>
    </div>

    <p>إذا لم تطلب هذه الدعوة، يمكنك تجاهل هذه الرسالة.</p>
@endsection
