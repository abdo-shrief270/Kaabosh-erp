@extends('reports.layout')

@section('title')
    الميزانية العمومية / Balance Sheet
@endsection

@section('date-range')
    كما في {{ $data['as_of_date'] }} / As of {{ $data['as_of_date'] }}
@endsection

@section('content')
    {{-- Assets Section --}}
    <div class="section-title">الأصول / Assets</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الرمز<br>Code</th>
                <th style="width: 40%;">اسم الحساب<br>Account Name</th>
                <th style="width: 20%;">المبلغ<br>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['assets']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group_code'] }}</td>
                    <td>{{ $group['group_name_ar'] }} / {{ $group['group_name_en'] }}</td>
                    <td></td>
                </tr>
                @foreach($group['accounts'] as $account)
                    <tr>
                        <td class="text-center">{{ $account['account_code'] }}</td>
                        <td>{{ $account['account_name_ar'] }} / {{ $account['account_name_en'] }}</td>
                        <td class="number">{{ number_format((float) $account['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="2" class="text-left">المجموع الفرعي / Subtotal</td>
                    <td class="number">{{ number_format((float) $group['subtotal'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">إجمالي الأصول / Total Assets</td>
                <td class="number">{{ number_format((float) $data['assets']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Liabilities Section --}}
    <div class="section-title">الخصوم / Liabilities</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الرمز<br>Code</th>
                <th style="width: 40%;">اسم الحساب<br>Account Name</th>
                <th style="width: 20%;">المبلغ<br>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['liabilities']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group_code'] }}</td>
                    <td>{{ $group['group_name_ar'] }} / {{ $group['group_name_en'] }}</td>
                    <td></td>
                </tr>
                @foreach($group['accounts'] as $account)
                    <tr>
                        <td class="text-center">{{ $account['account_code'] }}</td>
                        <td>{{ $account['account_name_ar'] }} / {{ $account['account_name_en'] }}</td>
                        <td class="number">{{ number_format((float) $account['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="2" class="text-left">المجموع الفرعي / Subtotal</td>
                    <td class="number">{{ number_format((float) $group['subtotal'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">إجمالي الخصوم / Total Liabilities</td>
                <td class="number">{{ number_format((float) $data['liabilities']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Equity Section --}}
    <div class="section-title">حقوق الملكية / Equity</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الرمز<br>Code</th>
                <th style="width: 40%;">اسم الحساب<br>Account Name</th>
                <th style="width: 20%;">المبلغ<br>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['equity']['groups'] as $group)
                <tr class="group-header">
                    <td>{{ $group['group_code'] }}</td>
                    <td>{{ $group['group_name_ar'] }} / {{ $group['group_name_en'] }}</td>
                    <td></td>
                </tr>
                @foreach($group['accounts'] as $account)
                    <tr>
                        <td class="text-center">{{ $account['account_code'] }}</td>
                        <td>{{ $account['account_name_ar'] }} / {{ $account['account_name_en'] }}</td>
                        <td class="number">{{ number_format((float) $account['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="2" class="text-left">المجموع الفرعي / Subtotal</td>
                    <td class="number">{{ number_format((float) $group['subtotal'], 2) }}</td>
                </tr>
            @endforeach
            {{-- Current Year Net Income --}}
            <tr class="{{ (float) $data['equity']['net_income'] >= 0 ? 'net-income-positive' : 'net-income-negative' }}">
                <td></td>
                <td class="bold">أرباح (خسائر) العام / Current Year Net Income (Loss)</td>
                <td class="number bold">{{ number_format((float) $data['equity']['net_income'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2">إجمالي حقوق الملكية / Total Equity</td>
                <td class="number">{{ number_format((float) $data['equity']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Total Liabilities & Equity --}}
    <table>
        <tbody>
            <tr class="grand-total-row">
                <td style="width: 50%;">إجمالي الخصوم وحقوق الملكية / Total Liabilities & Equity</td>
                <td class="number" style="width: 20%;">{{ number_format((float) $data['total_liabilities_and_equity'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Balance Check --}}
    <div style="text-align: center; margin-top: 10px;">
        @if($data['is_balanced'])
            <span class="balanced-badge balanced">الميزانية متوازنة / Balance Sheet is Balanced</span>
        @else
            <span class="balanced-badge unbalanced">الميزانية غير متوازنة / Balance Sheet is NOT Balanced</span>
            <br>
            <span style="font-size: 9px; color: #721c24;">
                الفرق / Difference: {{ number_format(abs((float) $data['assets']['total'] - (float) $data['total_liabilities_and_equity']), 2) }}
            </span>
        @endif
    </div>
@endsection
