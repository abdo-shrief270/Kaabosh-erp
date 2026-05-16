@extends('emails.layout')

@section('title', $subject ?? 'تذكير بالفاتورة')

@section('content')
    <h2>{{ $client_name ?? 'عزيزي العميل' }}</h2>

    <p>نود تذكيركم بالفاتورة التالية:</p>

    <div class="info-box">
        <table style="width: 100%; font-size: 14px;">
            <tr>
                <td style="padding: 6px 0; color: #888;">رقم الفاتورة:</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $invoice_number ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #888;">المبلغ المتبقي:</td>
                <td style="padding: 6px 0; font-weight: bold; color: #e74c3c;">{{ $amount ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #888;">تاريخ الاستحقاق:</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $due_date ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <p>يرجى سداد المبلغ المستحق في أقرب وقت ممكن لتجنب أي رسوم إضافية.</p>

    <p style="color: #888; font-size: 12px;">إذا كنت قد سددت بالفعل، يرجى تجاهل هذه الرسالة.</p>
@endsection
