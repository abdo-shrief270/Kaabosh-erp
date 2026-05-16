@extends('emails.layout')

@section('title', 'اشتراكك ينتهي قريباً')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>نود إعلامك أن اشتراك <strong>{{ $tenantName }}</strong> في محاسبي سينتهي خلال <strong>{{ $daysLeft }} {{ $daysLeft === 1 ? 'يوم' : 'أيام' }}</strong>.</p>

    <div class="info-box">
        <div class="label">لتجنب انقطاع الخدمة:</div>
        <div class="value" style="font-size: 14px;">
            قم بتجديد اشتراكك الآن للحفاظ على وصولك الكامل لجميع مزايا محاسبي.
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $renewUrl }}" class="btn">تجديد الاشتراك الآن</a>
    </div>

    <p style="color: #888; font-size: 13px;">إذا كنت قد جددت بالفعل، يرجى تجاهل هذه الرسالة.</p>
@endsection
