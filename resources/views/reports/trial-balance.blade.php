@extends('reports.layout')

@section('title')
    ميزان المراجعة / Trial Balance
@endsection

@section('date-range')
    @php
        $from = $data['rows'][0]['period_from'] ?? null;
        $to = $data['rows'][0]['period_to'] ?? null;
    @endphp
    @if(isset($from) && isset($to))
        من {{ $from }} إلى {{ $to }} / From {{ $from }} To {{ $to }}
    @else
        جميع الفترات / All Periods
    @endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 7%;">الرمز<br>Code</th>
                <th rowspan="2" style="width: 23%;">اسم الحساب<br>Account Name</th>
                <th colspan="2" style="width: 22%;">الرصيد الافتتاحي / Opening</th>
                <th colspan="2" style="width: 22%;">حركة الفترة / Period</th>
                <th colspan="2" style="width: 22%;">الرصيد الختامي / Closing</th>
            </tr>
            <tr>
                <th>مدين<br>Debit</th>
                <th>دائن<br>Credit</th>
                <th>مدين<br>Debit</th>
                <th>دائن<br>Credit</th>
                <th>مدين<br>Debit</th>
                <th>دائن<br>Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['rows'] as $row)
                <tr>
                    <td class="text-center">{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name_ar'] }}<br><span style="font-size: 8px; color: #666;">{{ $row['account_name_en'] }}</span></td>
                    <td class="number">{{ number_format((float) $row['opening_debit'], 2) }}</td>
                    <td class="number">{{ number_format((float) $row['opening_credit'], 2) }}</td>
                    <td class="number">{{ number_format((float) $row['period_debit'], 2) }}</td>
                    <td class="number">{{ number_format((float) $row['period_credit'], 2) }}</td>
                    <td class="number">{{ number_format((float) $row['closing_debit'], 2) }}</td>
                    <td class="number">{{ number_format((float) $row['closing_credit'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="grand-total-row">
                <td colspan="2">المجموع / Totals</td>
                <td class="number">{{ number_format((float) $data['totals']['opening_debit'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['totals']['opening_credit'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['totals']['period_debit'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['totals']['period_credit'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['totals']['closing_debit'], 2) }}</td>
                <td class="number">{{ number_format((float) $data['totals']['closing_credit'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Balance verification --}}
    <div style="text-align: center; margin-top: 10px;">
        @php
            $isBalanced = bccomp($data['totals']['closing_debit'], $data['totals']['closing_credit'], 2) === 0;
        @endphp
        @if($isBalanced)
            <span class="balanced-badge balanced">الميزان متوازن / Trial Balance is Balanced</span>
        @else
            <span class="balanced-badge unbalanced">الميزان غير متوازن / Trial Balance is NOT Balanced</span>
            <br>
            <span style="font-size: 9px; color: #721c24;">
                الفرق / Difference: {{ number_format(abs((float) $data['totals']['closing_debit'] - (float) $data['totals']['closing_credit']), 2) }}
            </span>
        @endif
    </div>
@endsection
