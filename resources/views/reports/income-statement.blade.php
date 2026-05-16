@extends('reports.layout')

@section('title')
    قائمة الدخل / Income Statement
@endsection

@section('date-range')
    @if($data['period']['from'] && $data['period']['to'])
        من {{ $data['period']['from'] }} إلى {{ $data['period']['to'] }}
        / From {{ $data['period']['from'] }} To {{ $data['period']['to'] }}
    @elseif($data['period']['to'])
        حتى {{ $data['period']['to'] }} / Up to {{ $data['period']['to'] }}
    @else
        جميع الفترات / All Periods
    @endif
@endsection

@section('content')
    {{-- Revenue Section --}}
    <div class="section-title">الإيرادات / Revenue</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الرمز<br>Code</th>
                <th style="width: 35%;">اسم الحساب<br>Account Name</th>
                <th style="width: 20%;">المبلغ<br>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['revenue']['groups'] as $group)
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
                <td colspan="2">إجمالي الإيرادات / Total Revenue</td>
                <td class="number">{{ number_format((float) $data['revenue']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Expenses Section --}}
    <div class="section-title">المصروفات / Expenses</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الرمز<br>Code</th>
                <th style="width: 35%;">اسم الحساب<br>Account Name</th>
                <th style="width: 20%;">المبلغ<br>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['expenses']['groups'] as $group)
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
                <td colspan="2">إجمالي المصروفات / Total Expenses</td>
                <td class="number">{{ number_format((float) $data['expenses']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Net Income --}}
    <table>
        <tbody>
            <tr class="grand-total-row">
                <td style="width: 45%;">
                    @if((float) $data['net_income'] >= 0)
                        صافي الربح / Net Income
                    @else
                        صافي الخسارة / Net Loss
                    @endif
                </td>
                <td class="number" style="width: 20%;">{{ number_format((float) $data['net_income'], 2) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
