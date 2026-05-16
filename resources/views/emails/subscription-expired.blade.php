@extends('emails.layout')

@section('title', 'اشتراكك انتهى')

@section('content')
    <h2>مرحباً {{ $userName }}!</h2>

    <p>نأسف لإعلامك أن اشتراك <strong>{{ $tenantName }}</strong> في محاسبي قد انتهى.</p>

    @if($reason === 'trial_expired')
        <p>انتهت فترتك التجريبية المجانية. لمتابعة استخدام محاسبي، يرجى اختيار خطة اشتراك مناسبة.</p>
    @elseif($reason === 'payment_failed')
        <p>لم نتمكن من تحصيل الدفعة بعد انتهاء فترة السماح. يرجى تحديث بيانات الدفع وتجديد الاشتراك.</p>
    @else
        <p>يرجى تجديد اشتراكك للعودة إلى استخدام النظام.</p>
    @endif

    <div class="info-box">
        <div class="label">ملاحظة مهمة:</div>
        <div class="value" style="font-size: 14px;">
            بياناتك آمنة ومحفوظة. سيتم الاحتفاظ بها لمدة 30 يوماً يمكنك خلالها تجديد اشتراكك.
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url', config('app.url')) }}/subscription" class="btn">تجديد الاشتراك</a>
    </div>
@endsection
