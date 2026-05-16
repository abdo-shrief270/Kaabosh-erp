@extends('reports.layout')

@section('title', 'كشف أرباح المستثمر - ' . $investor->name)

@section('date-range')
    شهر {{ $month }} / {{ $year }}
@endsection

@section('content')
    <div class="section-title">بيانات المستثمر</div>
    <table>
        <tr>
            <td class="bold" style="width: 25%;">الاسم</td>
            <td style="width: 25%;">{{ $investor->name }}</td>
            <td class="bold" style="width: 25%;">البريد الإلكتروني</td>
            <td style="width: 25%;">{{ $investor->email ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bold">الهاتف</td>
            <td>{{ $investor->phone ?? '-' }}</td>
            <td class="bold">تاريخ الانضمام</td>
            <td>{{ $investor->join_date?->toDateString() }}</td>
        </tr>
    </table>

    <div class="section-title">تفاصيل الأرباح حسب المنشأة</div>
    <table>
        <thead>
            <tr>
                <th>المنشأة</th>
                <th>الإيرادات</th>
                <th>المصروفات</th>
                <th>صافي الربح</th>
                <th>نسبة الملكية</th>
                <th>حصة المستثمر</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distributions as $dist)
                <tr>
                    <td>{{ $dist->tenant?->name ?? '-' }}</td>
                    <td class="number">{{ number_format((float) $dist->tenant_revenue, 2) }}</td>
                    <td class="number">{{ number_format((float) $dist->tenant_expenses, 2) }}</td>
                    <td class="number {{ (float) $dist->net_profit >= 0 ? '' : 'net-income-negative' }}">
                        {{ number_format((float) $dist->net_profit, 2) }}
                    </td>
                    <td class="number">{{ $dist->ownership_percentage }}%</td>
                    <td class="number bold">{{ number_format((float) $dist->investor_share, 2) }}</td>
                    <td class="text-center">{{ $dist->status->labelAr() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">الملخص</div>
    <table>
        <tr>
            <td class="bold">إجمالي الإيرادات</td>
            <td class="number">{{ number_format($totalRevenue, 2) }} ج.م.</td>
        </tr>
        <tr>
            <td class="bold">إجمالي المصروفات</td>
            <td class="number">{{ number_format($totalExpenses, 2) }} ج.م.</td>
        </tr>
        <tr>
            <td class="bold">إجمالي صافي الربح</td>
            <td class="number">{{ number_format($totalNetProfit, 2) }} ج.م.</td>
        </tr>
        <tr class="grand-total-row">
            <td class="bold">إجمالي حصة المستثمر</td>
            <td class="number bold">{{ number_format($totalShare, 2) }} ج.م.</td>
        </tr>
    </table>
@endsection
