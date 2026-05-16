@extends('emails.layout')

@section('title', 'تم إرسال الفاتورة')

@section('content')
    <h2>تم إرسال الفاتورة بنجاح</h2>

    <p>تم إرسال الفاتورة رقم <strong>{{ $invoiceNumber }}</strong> إلى العميل <strong>{{ $clientName }}</strong>.</p>

    <div class="info-box">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>
                <span class="label">رقم الفاتورة</span><br>
                <span class="value">{{ $invoiceNumber }}</span>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>
                <span class="label">العميل</span><br>
                <span class="value">{{ $clientName }}</span>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>
                <span class="label">المبلغ الإجمالي</span><br>
                <span class="value">{{ $totalAmount }} ج.م.</span>
            </span>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="{{ $actionUrl }}" class="btn">عرض الفاتورة</a>
    </div>
@endsection
