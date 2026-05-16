@extends('reports.layout')

@section('title')
    إقرار ضريبة القيمة المضافة / VAT Return (Form 10)
@endsection

@section('date-range')
    من {{ $data['period']['from'] }} إلى {{ $data['period']['to'] }}
    / From {{ $data['period']['from'] }} To {{ $data['period']['to'] }}
@endsection

@section('content')
    {{-- Output VAT Section --}}
    <div class="section-title">ضريبة المخرجات (على المبيعات) / Output VAT (on Sales)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">البيان / Description</th>
                <th style="width: 25%;">المبلغ / Amount</th>
                <th style="width: 25%;">الضريبة / VAT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>مبيعات خاضعة للضريبة / Taxable Sales ({{ $data['output_vat']['invoice_count'] }} فاتورة)</td>
                <td class="number">{{ number_format((float) $data['output_vat']['taxable_amount'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['output_vat']['sales_vat'], 2) }}</td>
            </tr>
            @if((float) $data['output_vat']['credit_notes_vat'] > 0)
                <tr>
                    <td>إشعارات دائنة / Credit Notes ({{ $data['output_vat']['credit_notes_count'] }})</td>
                    <td class="number" style="color: #c0392b;">({{ number_format((float) $data['output_vat']['credit_notes_vat'], 2) }})</td>
                    <td class="number" style="color: #c0392b;">({{ number_format((float) $data['output_vat']['credit_notes_vat'], 2) }})</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>إجمالي ضريبة المخرجات / Total Output VAT</td>
                <td class="number"></td>
                <td class="number">{{ number_format((float) $data['output_vat']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Input VAT Section --}}
    <div class="section-title">ضريبة المدخلات (على المشتريات) / Input VAT (on Purchases)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">البيان / Description</th>
                <th style="width: 25%;">عدد القيود / Entries</th>
                <th style="width: 25%;">الضريبة / VAT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>ضريبة مدخلات مدفوعة / Input VAT Paid</td>
                <td class="number">{{ $data['input_vat']['entry_count'] }}</td>
                <td class="number">{{ number_format((float) $data['input_vat']['total'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>إجمالي ضريبة المدخلات / Total Input VAT</td>
                <td class="number"></td>
                <td class="number">{{ number_format((float) $data['input_vat']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- VAT by Rate Breakdown --}}
    @if(count($data['vat_by_rate']) > 0)
        <div class="section-title">تحليل الضريبة حسب النسبة / VAT Breakdown by Rate</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">النسبة / Rate</th>
                    <th style="width: 30%;">المبلغ الخاضع / Taxable Amount</th>
                    <th style="width: 30%;">مبلغ الضريبة / VAT Amount</th>
                    <th style="width: 20%;">عدد البنود / Lines</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['vat_by_rate'] as $rate)
                    <tr>
                        <td class="text-center">{{ $rate['rate_label'] }}</td>
                        <td class="number">{{ number_format((float) $rate['taxable_amount'], 2) }}</td>
                        <td class="number">{{ number_format((float) $rate['vat_amount'], 2) }}</td>
                        <td class="text-center">{{ $rate['line_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Net VAT Summary --}}
    <table>
        <tbody>
            <tr class="grand-total-row">
                <td style="width: 50%;">
                    @if($data['is_payable'])
                        صافي الضريبة المستحقة / Net VAT Payable
                    @else
                        صافي الضريبة المستردة / Net VAT Refundable
                    @endif
                </td>
                <td class="number" style="width: 25%;">{{ $data['currency'] }}</td>
                <td class="number" style="width: 25%;">{{ number_format(abs((float) $data['net_vat']), 2) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
