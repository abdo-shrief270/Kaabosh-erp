@extends('emails.layout')

@section('title', 'رسالة تواصل جديدة')

@section('content')
    <h2>📩 رسالة تواصل جديدة</h2>

    <div class="info-box">
        <table style="width: 100%; font-size: 14px;">
            <tr>
                <td style="padding: 6px 0; color: #888; width: 100px;">الاسم:</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $submission->name }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #888;">البريد:</td>
                <td style="padding: 6px 0;"><a href="mailto:{{ $submission->email }}">{{ $submission->email }}</a></td>
            </tr>
            @if($submission->phone)
            <tr>
                <td style="padding: 6px 0; color: #888;">الهاتف:</td>
                <td style="padding: 6px 0;">{{ $submission->phone }}</td>
            </tr>
            @endif
            @if($submission->company)
            <tr>
                <td style="padding: 6px 0; color: #888;">الشركة:</td>
                <td style="padding: 6px 0;">{{ $submission->company }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 6px 0; color: #888;">الموضوع:</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $submission->subject }}</td>
            </tr>
        </table>
    </div>

    <p><strong>الرسالة:</strong></p>
    <div style="background: #f8f9fa; padding: 16px; border-radius: 6px; white-space: pre-wrap; font-size: 14px; color: #444;">{{ $submission->message }}</div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="{{ config('app.url') }}/admin/contacts" class="btn">عرض في لوحة الإدارة</a>
    </div>
@endsection
