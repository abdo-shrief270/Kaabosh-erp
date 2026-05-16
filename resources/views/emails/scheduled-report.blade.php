@extends('emails.layout')

@section('title', $subject)

@section('content')
    <h2>{{ $reportName }}</h2>

    <p>مرفق التقرير المجدول: <strong>{{ $reportName }}</strong></p>

    <div class="info-box">
        <div class="label">تاريخ الإرسال</div>
        <div class="value">{{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <p>يرجى مراجعة الملف المرفق. تم إنشاء هذا التقرير تلقائياً بناءً على الجدول الزمني المحدد.</p>
@endsection
