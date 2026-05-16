@extends('reports.layout')

@section('title')
    قائمة التدفقات النقدية / Cash Flow Statement
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
    {{-- Operating Activities --}}
    <div class="section-title">أنشطة التشغيل / Operating Activities</div>
    <table>
        <thead>
            <tr>
                <th style="width: 55%;">البيان / Description</th>
                <th style="width: 20%;">المبلغ / Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bold">
                <td>صافي الدخل / Net Income</td>
                <td class="number">{{ number_format((float) $data['operating']['net_income'], 2) }}</td>
            </tr>

            @if(count($data['operating']['adjustments']) > 0)
                <tr class="group-header">
                    <td colspan="2">تعديلات / Adjustments</td>
                </tr>
                @foreach($data['operating']['adjustments'] as $item)
                    <tr>
                        <td style="padding-right: 20px;">{{ $item['description_ar'] }} / {{ $item['description_en'] }}</td>
                        <td class="number">{{ number_format((float) $item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            @endif

            @if(count($data['operating']['working_capital_changes']) > 0)
                <tr class="group-header">
                    <td colspan="2">التغيرات في رأس المال العامل / Working Capital Changes</td>
                </tr>
                @foreach($data['operating']['working_capital_changes'] as $item)
                    <tr>
                        <td style="padding-right: 20px;">{{ $item['description_ar'] }} / {{ $item['description_en'] }}</td>
                        <td class="number">{{ number_format((float) $item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="total-row">
                <td>صافي النقد من أنشطة التشغيل / Net Cash from Operating Activities</td>
                <td class="number">{{ number_format((float) $data['operating']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Investing Activities --}}
    <div class="section-title">أنشطة الاستثمار / Investing Activities</div>
    <table>
        <thead>
            <tr>
                <th style="width: 55%;">البيان / Description</th>
                <th style="width: 20%;">المبلغ / Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['investing']['items'] as $item)
                <tr>
                    <td>{{ $item['description_ar'] }} / {{ $item['description_en'] }}</td>
                    <td class="number">{{ number_format((float) $item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center" style="color: #999;">لا توجد أنشطة / No activities</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>صافي النقد من أنشطة الاستثمار / Net Cash from Investing Activities</td>
                <td class="number">{{ number_format((float) $data['investing']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Financing Activities --}}
    <div class="section-title">أنشطة التمويل / Financing Activities</div>
    <table>
        <thead>
            <tr>
                <th style="width: 55%;">البيان / Description</th>
                <th style="width: 20%;">المبلغ / Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['financing']['items'] as $item)
                <tr>
                    <td>{{ $item['description_ar'] }} / {{ $item['description_en'] }}</td>
                    <td class="number">{{ number_format((float) $item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center" style="color: #999;">لا توجد أنشطة / No activities</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>صافي النقد من أنشطة التمويل / Net Cash from Financing Activities</td>
                <td class="number">{{ number_format((float) $data['financing']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Summary --}}
    <div class="section-title">ملخص / Summary</div>
    <table>
        <tbody>
            <tr>
                <td style="width: 55%;" class="bold">صافي التغير في النقد / Net Change in Cash</td>
                <td class="number bold" style="width: 20%;">{{ number_format((float) $data['net_change'], 2) }}</td>
            </tr>
            <tr>
                <td class="bold">رصيد النقد أول الفترة / Opening Cash Balance</td>
                <td class="number bold">{{ number_format((float) $data['opening_cash'], 2) }}</td>
            </tr>
            <tr class="grand-total-row">
                <td>رصيد النقد آخر الفترة / Closing Cash Balance</td>
                <td class="number">{{ number_format((float) $data['closing_cash'], 2) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
