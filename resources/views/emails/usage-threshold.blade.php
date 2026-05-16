@extends('emails.layout')

@section('title', $isAtCap ? 'تم بلوغ الحد - Limit reached' : 'تنبيه استخدام - Usage warning')

@section('content')
    <h2>
        {{ $isAtCap ? 'تم بلوغ الحد' : 'تنبيه استخدام' }}
        <span style="color:#94a3b8; font-size:14px;">/ {{ $isAtCap ? 'Limit reached' : 'Usage warning' }}</span>
    </h2>

    <p>
        وصل استخدام مساحة عمل <strong>{{ $tenantName }}</strong> إلى <strong>{{ $percent }}%</strong>
        من حصة {{ $metricLabelAr }}.
    </p>
    <p style="color:#475569; font-size:14px;">
        Your <strong>{{ $tenantName }}</strong> workspace has reached <strong>{{ $percent }}%</strong>
        of its {{ $metricLabel }} allowance.
    </p>

    @if ($isAtCap)
        <div class="info-box">
            <div class="label">إجراء مطلوب / Action required</div>
            <div class="value" style="font-size: 14px; line-height: 1.6;">
                لقد بلغت الحد لهذه الفترة. قد تُمنع الإضافات الجديدة حتى ترفع الحد أو تنتظر دورة الفوترة الجديدة.
                <br>
                <span style="color:#475569;">
                    You've hit the cap for this period. New {{ $metricLabel }} actions may be blocked
                    until you raise the limit or wait for the next billing cycle.
                </span>
            </div>
        </div>
    @else
        <p>
            ستبلغ الحد قريباً بهذا المعدل. خطّط مسبقاً حتى لا يتعطل العمل.
        </p>
        <p style="color:#475569; font-size:14px;">
            You'll hit the cap soon at the current pace. Plan ahead so the workflow doesn't get interrupted.
        </p>
    @endif

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url', config('app.url')) }}/subscription/add-ons" class="btn">
            عرض الإضافات / View add-ons
        </a>
    </div>

    <p style="color:#94a3b8; font-size:12px; margin-top:24px;">
        يمكنك أيضاً ترقية باقتك من صفحة الاشتراك. /
        You can also upgrade your plan from your subscription page.
    </p>
@endsection
