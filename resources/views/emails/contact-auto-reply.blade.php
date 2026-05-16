@extends('emails.layout')

@section('title', 'شكراً لتواصلك')

@section('content')
    <h2>مرحباً {{ $submission->name }}!</h2>

    <p>شكراً لتواصلك مع فريق محاسبي. تم استلام رسالتك بنجاح.</p>

    <div class="info-box">
        <div class="label">تفاصيل رسالتك:</div>
        <div class="value" style="font-size: 14px;">
            <strong>الموضوع:</strong> {{ $submission->subject }}<br>
            <strong>الرسالة:</strong> {{ Str::limit($submission->message, 200) }}
        </div>
    </div>

    <p>سيقوم فريقنا بمراجعة رسالتك والرد عليك في أقرب وقت ممكن، عادةً خلال ٢٤ ساعة عمل.</p>

    <p style="color: #888; font-size: 13px;">هذه رسالة تلقائية، يرجى عدم الرد عليها مباشرة.</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    <h2 style="direction: ltr; text-align: left;">Hello {{ $submission->name }}!</h2>

    <p style="direction: ltr; text-align: left;">Thank you for contacting Muhasebi. Your message has been received successfully.</p>

    <p style="direction: ltr; text-align: left;">Our team will review your message and respond as soon as possible, usually within 24 business hours.</p>
@endsection
