@extends('emails.layout')

@section('title', 'تم استلام دفعة')

@section('content')
    <h2>تم استلام دفعة جديدة</h2>

    <p>تم تسجيل دفعة جديدة للفاتورة رقم <strong>{{ $invoiceNumber }}</strong>.</p>

    <div class="info-box">
        <div style="margin-bottom: 8px;">
            <span class="label">المبلغ المستلم</span><br>
            <span class="value">{{ $amount }} ج.م.</span>
        </div>
        <div style="margin-bottom: 8px;">
            <span class="label">رقم الفاتورة</span><br>
            <span class="value">{{ $invoiceNumber }}</span>
        </div>
        <div>
            <span class="label">طريقة الدفع</span><br>
            <span class="value">{{ $paymentMethod }}</span>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $actionUrl }}" class="btn">عرض التفاصيل</a>
    </div>
@endsection
