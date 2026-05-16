@extends('reports.layout')

@section('title')
    تقرير ضريبة الخصم والإضافة / Withholding Tax Report
@endsection

@section('date-range')
    من {{ $data['period']['from'] }} إلى {{ $data['period']['to'] }}
    / From {{ $data['period']['from'] }} To {{ $data['period']['to'] }}
@endsection

@section('content')
    @forelse($data['by_account'] as $account)
        <div class="section-title">
            {{ $account['account_code'] }} - {{ $account['account_name_ar'] }} / {{ $account['account_name_en'] }}
            ({{ $account['entries_count'] }} قيد)
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">التاريخ / Date</th>
                    <th style="width: 15%;">رقم القيد / Entry #</th>
                    <th style="width: 45%;">البيان / Description</th>
                    <th style="width: 25%;">المبلغ المحتجز / Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($account['entries'] as $entry)
                    <tr>
                        <td class="text-center">{{ $entry['date'] }}</td>
                        <td class="text-center">{{ $entry['entry_number'] }}</td>
                        <td>{{ $entry['description'] ?? '-' }}</td>
                        <td class="number">{{ number_format((float) $entry['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">المجموع / Total</td>
                    <td class="number">{{ number_format((float) $account['total_withheld'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <div style="text-align: center; padding: 40px; color: #999;">
            لا توجد قيود ضريبة خصم وإضافة في هذه الفترة
            <br>
            No withholding tax entries found for this period
        </div>
    @endforelse

    {{-- Grand Total --}}
    @if(count($data['by_account']) > 0)
        <table>
            <tbody>
                <tr class="grand-total-row">
                    <td style="width: 50%;">إجمالي الضريبة المحتجزة / Total Tax Withheld</td>
                    <td class="number" style="width: 25%;">{{ $data['currency'] }}</td>
                    <td class="number" style="width: 25%;">{{ number_format((float) $data['total_withheld'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
@endsection
