<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    @php
        $safeAccent = preg_match('/^#[0-9a-fA-F]{3,6}$/', $accentColor ?? '') ? $accentColor : '#2c3e50';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #333; direction: rtl; line-height: 1.5; }

        .header { background-color: {{ $safeAccent }}; color: #fff; padding: 30px; display: flex; }
        .header-left { flex: 1; }
        .header-right { text-align: left; }
        .company-name { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .invoice-title { font-size: 28px; font-weight: bold; letter-spacing: 2px; }
        .invoice-number { font-size: 14px; opacity: 0.8; }

        @if($headerText)
        .header-banner { background: {{ $safeAccent }}22; padding: 8px 30px; font-size: 11px; color: {{ $safeAccent }}; text-align: center; }
        @endif

        .meta-section { padding: 25px 30px; display: flex; gap: 30px; }
        .meta-box { flex: 1; }
        .meta-label { font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .meta-value { font-size: 13px; font-weight: 600; color: #333; }

        .lines-table { width: 100%; border-collapse: collapse; margin: 0 30px; }
        .lines-table th { background: #f8f9fa; padding: 10px 12px; text-align: right; font-size: 10px; color: #666; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .lines-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        .lines-table .amount { text-align: left; direction: ltr; }

        .totals { padding: 0 30px; margin-top: 15px; }
        .totals-table { margin-right: auto; border-collapse: collapse; }
        .totals-table td { padding: 6px 20px; font-size: 12px; }
        .totals-table .total-row { font-size: 16px; font-weight: bold; color: {{ $safeAccent }}; border-top: 2px solid {{ $safeAccent }}; }

        .notes { padding: 20px 30px; margin-top: 15px; }
        .notes-title { font-size: 11px; font-weight: bold; color: #666; margin-bottom: 4px; }
        .notes-text { font-size: 11px; color: #888; }

        .footer { position: fixed; bottom: 0; width: 100%; padding: 15px 30px; border-top: 1px solid #eee; font-size: 10px; color: #999; text-align: center; }

        .badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-sent { background: #cce5ff; color: #004085; }
        .badge-draft { background: #f8f9fa; color: #6c757d; }
        .badge-overdue { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%"><tr>
            <td style="vertical-align: top;">
                @if($showLogo && $logoUrl && file_exists($logoUrl))
                    <img src="{{ $logoUrl }}" height="40" style="margin-bottom: 8px;">
                @endif
                <div class="company-name">{{ $tenant->name }}</div>
                @if($tenant->address)<div style="font-size: 11px; opacity: 0.7;">{{ $tenant->address }}</div>@endif
                @if($tenant->tax_id)<div style="font-size: 11px; opacity: 0.7;">{{ __('الرقم الضريبي') }}: {{ $tenant->tax_id }}</div>@endif
            </td>
            <td style="text-align: left; vertical-align: top;">
                <div class="invoice-title">فاتورة</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 8px;">
                    <span class="badge badge-{{ strtolower($invoice->status->value ?? $invoice->status) }}">{{ $invoice->status->labelAr() ?? $invoice->status }}</span>
                </div>
            </td>
        </tr></table>
    </div>

    @if($headerText)
    <div class="header-banner">{{ $headerText }}</div>
    @endif

    <div class="meta-section">
        <table width="100%"><tr>
            <td width="50%" style="vertical-align: top;">
                <div class="meta-label">العميل</div>
                <div class="meta-value">{{ $invoice->client->name }}</div>
                @if($invoice->client->tax_id)<div style="font-size: 11px; color: #888;">رقم ضريبي: {{ $invoice->client->tax_id }}</div>@endif
                @if($invoice->client->address)<div style="font-size: 11px; color: #888;">{{ $invoice->client->address }}</div>@endif
            </td>
            <td width="25%" style="vertical-align: top;">
                <div class="meta-label">تاريخ الفاتورة</div>
                <div class="meta-value">{{ $invoice->date->format('Y/m/d') }}</div>
            </td>
            <td width="25%" style="vertical-align: top;">
                <div class="meta-label">تاريخ الاستحقاق</div>
                <div class="meta-value">{{ $invoice->due_date?->format('Y/m/d') ?? '-' }}</div>
            </td>
        </tr></table>
    </div>

    <table class="lines-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الوصف</th>
                <th class="amount">الكمية</th>
                <th class="amount">سعر الوحدة</th>
                @if($showVatBreakdown)
                <th class="amount">الخصم %</th>
                <th class="amount">ض.ق.م %</th>
                @endif
                <th class="amount">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $line->description }}</td>
                <td class="amount">{{ number_format($line->quantity, 2) }}</td>
                <td class="amount">{{ number_format($line->unit_price, 2) }}</td>
                @if($showVatBreakdown)
                <td class="amount">{{ $line->discount_percent > 0 ? number_format($line->discount_percent, 1) . '%' : '-' }}</td>
                <td class="amount">{{ number_format($line->vat_rate, 1) }}%</td>
                @endif
                <td class="amount"><strong>{{ number_format($line->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td style="color: #888;">المجموع الفرعي:</td>
                <td class="amount">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td style="color: #888;">الخصم:</td>
                <td class="amount">-{{ number_format($invoice->discount_amount, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @endif
            @if($showVatBreakdown && $invoice->vat_amount > 0)
            <tr>
                <td style="color: #888;">ضريبة القيمة المضافة:</td>
                <td class="amount">{{ number_format($invoice->vat_amount, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>الإجمالي:</td>
                <td class="amount">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @if($invoice->amount_paid > 0)
            <tr>
                <td style="color: #888;">المدفوع:</td>
                <td class="amount">{{ number_format($invoice->amount_paid, 2) }} {{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">المتبقي:</td>
                <td class="amount" style="font-weight: bold;">{{ number_format($invoice->total - $invoice->amount_paid, 2) }} {{ $invoice->currency }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($showPaymentTerms && ($invoice->terms || $invoice->notes))
    <div class="notes">
        @if($invoice->terms)
            <div class="notes-title">شروط الدفع</div>
            <div class="notes-text">{{ $invoice->terms }}</div>
        @endif
        @if($invoice->notes)
            <div class="notes-title" style="margin-top: 10px;">ملاحظات</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        @endif
    </div>
    @endif

    <div class="footer">
        @if($footerText)
            {{ $footerText }}
        @else
            {{ $tenant->name }} — تم الإنشاء في {{ $generatedAt }}
        @endif
    </div>
</body>
</html>
