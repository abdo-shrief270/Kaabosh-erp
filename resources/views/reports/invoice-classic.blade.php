<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $safeAccent = preg_match('/^#[0-9a-fA-F]{3,6}$/', $accentColor ?? '') ? $accentColor : '#2c3e50';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #333; direction: rtl; line-height: 1.6; padding: 40px; }

        .header { border-bottom: 3px solid {{ $safeAccent }}; padding-bottom: 20px; margin-bottom: 25px; }
        .company-name { font-size: 20px; font-weight: bold; color: {{ $safeAccent }}; }

        .invoice-meta { margin-bottom: 25px; }
        .invoice-meta table { width: 100%; }
        .invoice-meta .label { font-weight: bold; color: #555; width: 120px; }

        .invoice-title { font-size: 24px; font-weight: bold; color: {{ $safeAccent }}; text-align: center; margin: 20px 0; border: 2px solid {{ $safeAccent }}; padding: 8px; }

        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: {{ $safeAccent }}; color: #fff; padding: 8px 10px; text-align: right; font-size: 11px; }
        table.items td { padding: 8px 10px; border: 1px solid #ddd; font-size: 11px; }
        table.items .amount { text-align: left; direction: ltr; }
        table.items tr:nth-child(even) { background: #f9f9f9; }

        .totals { margin-top: 15px; }
        .totals table { margin-right: auto; }
        .totals td { padding: 5px 15px; font-size: 12px; }
        .totals .grand-total { font-size: 16px; font-weight: bold; background: {{ $safeAccent }}11; }

        .notes { margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 2px solid {{ $safeAccent }}; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%"><tr>
            <td>
                @if($showLogo && $logoUrl && file_exists($logoUrl))
                    <img src="{{ $logoUrl }}" height="50" style="margin-bottom: 5px;"><br>
                @endif
                <span class="company-name">{{ $tenant->name }}</span><br>
                @if($tenant->address)<span style="color: #888;">{{ $tenant->address }}</span><br>@endif
                @if($tenant->tax_id)<span style="color: #888;">الرقم الضريبي: {{ $tenant->tax_id }}</span>@endif
            </td>
            <td style="text-align: left; vertical-align: top;">
                <strong>فاتورة رقم:</strong> {{ $invoice->invoice_number }}<br>
                <strong>التاريخ:</strong> {{ $invoice->date->format('Y/m/d') }}<br>
                <strong>الاستحقاق:</strong> {{ $invoice->due_date?->format('Y/m/d') ?? '-' }}<br>
                <strong>الحالة:</strong> {{ $invoice->status->labelAr() ?? $invoice->status }}
            </td>
        </tr></table>
    </div>

    <div class="invoice-meta">
        <table><tr>
            <td class="label">العميل:</td>
            <td>
                <strong>{{ $invoice->client->name }}</strong>
                @if($invoice->client->tax_id) — رقم ضريبي: {{ $invoice->client->tax_id }}@endif
                @if($invoice->client->address)<br>{{ $invoice->client->address }}@endif
            </td>
        </tr></table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>الوصف</th>
                <th class="amount" style="width: 70px;">الكمية</th>
                <th class="amount" style="width: 90px;">سعر الوحدة</th>
                <th class="amount" style="width: 60px;">ض.ق.م</th>
                <th class="amount" style="width: 100px;">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $line->description }}</td>
                <td class="amount">{{ number_format($line->quantity, 2) }}</td>
                <td class="amount">{{ number_format($line->unit_price, 2) }}</td>
                <td class="amount">{{ number_format($line->vat_rate, 0) }}%</td>
                <td class="amount"><strong>{{ number_format($line->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>المجموع الفرعي:</td><td class="amount">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td></tr>
            @if($invoice->discount_amount > 0)
            <tr><td>الخصم:</td><td class="amount">-{{ number_format($invoice->discount_amount, 2) }}</td></tr>
            @endif
            @if($invoice->vat_amount > 0)
            <tr><td>ض.ق.م:</td><td class="amount">{{ number_format($invoice->vat_amount, 2) }}</td></tr>
            @endif
            <tr class="grand-total"><td><strong>الإجمالي:</strong></td><td class="amount"><strong>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</strong></td></tr>
            @if($invoice->amount_paid > 0)
            <tr><td>المدفوع:</td><td class="amount">{{ number_format($invoice->amount_paid, 2) }}</td></tr>
            <tr><td><strong>المتبقي:</strong></td><td class="amount"><strong>{{ number_format($invoice->total - $invoice->amount_paid, 2) }}</strong></td></tr>
            @endif
        </table>
    </div>

    @if($invoice->terms || $invoice->notes)
    <div class="notes">
        @if($invoice->terms)<strong>شروط الدفع:</strong> {{ $invoice->terms }}<br>@endif
        @if($invoice->notes)<strong>ملاحظات:</strong> {{ $invoice->notes }}@endif
    </div>
    @endif

    <div class="footer">
        {{ $footerText ?? $tenant->name . ' — تم الإنشاء في ' . $generatedAt }}
    </div>
</body>
</html>
