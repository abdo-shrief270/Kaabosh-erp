@extends('reports.layout')

@section('title', 'قسيمة الراتب - ' . $user->name)

@section('date-range')
    شهر {{ $run->month }} / {{ $run->year }}
@endsection

@section('content')
    <div class="section-title">بيانات الموظف</div>
    <table>
        <tr>
            <td class="bold" style="width: 25%;">الاسم</td>
            <td style="width: 25%;">{{ $user->name }}</td>
            <td class="bold" style="width: 25%;">المسمى الوظيفي</td>
            <td style="width: 25%;">{{ $employee->job_title ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bold">القسم</td>
            <td>{{ $employee->department ?? '-' }}</td>
            <td class="bold">تاريخ التعيين</td>
            <td>{{ $employee->hire_date?->toDateString() }}</td>
        </tr>
        <tr>
            <td class="bold">رقم التأمينات</td>
            <td>{{ $employee->social_insurance_number ?? '-' }}</td>
            <td class="bold">الحساب البنكي</td>
            <td>{{ $employee->bank_account ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">الاستحقاقات</div>
    <table>
        <thead>
            <tr>
                <th>البند</th>
                <th>المبلغ (ج.م.)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>الراتب الأساسي</td>
                <td class="number">{{ number_format((float) $item->base_salary, 2) }}</td>
            </tr>
            @if((float) $item->allowances > 0)
            <tr>
                <td>البدلات</td>
                <td class="number">{{ number_format((float) $item->allowances, 2) }}</td>
            </tr>
            @endif
            @if((float) $item->overtime_amount > 0)
            <tr>
                <td>العمل الإضافي ({{ $item->overtime_hours }} ساعة)</td>
                <td class="number">{{ number_format((float) $item->overtime_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="subtotal-row">
                <td class="bold">إجمالي الاستحقاقات</td>
                <td class="number bold">{{ number_format((float) $item->gross_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">الاستقطاعات</div>
    <table>
        <thead>
            <tr>
                <th>البند</th>
                <th>المبلغ (ج.م.)</th>
            </tr>
        </thead>
        <tbody>
            @if((float) $item->social_insurance_employee > 0)
            <tr>
                <td>التأمينات الاجتماعية (حصة الموظف)</td>
                <td class="number">{{ number_format((float) $item->social_insurance_employee, 2) }}</td>
            </tr>
            @endif
            @if((float) $item->income_tax > 0)
            <tr>
                <td>ضريبة الدخل</td>
                <td class="number">{{ number_format((float) $item->income_tax, 2) }}</td>
            </tr>
            @endif
            @if((float) $item->other_deductions > 0)
            <tr>
                <td>استقطاعات أخرى</td>
                <td class="number">{{ number_format((float) $item->other_deductions, 2) }}</td>
            </tr>
            @endif
            <tr class="subtotal-row">
                <td class="bold">إجمالي الاستقطاعات</td>
                <td class="number bold">{{ number_format((float) $item->social_insurance_employee + (float) $item->income_tax + (float) $item->other_deductions, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr class="grand-total-row">
            <td class="bold" style="width: 70%;">صافي الراتب</td>
            <td class="number bold" style="width: 30%;">{{ number_format((float) $item->net_salary, 2) }} ج.م.</td>
        </tr>
    </table>

    @if((float) $item->social_insurance_employer > 0)
    <div class="section-title">معلومات إضافية</div>
    <table>
        <tr>
            <td>حصة صاحب العمل في التأمينات</td>
            <td class="number">{{ number_format((float) $item->social_insurance_employer, 2) }} ج.م.</td>
        </tr>
    </table>
    @endif
@endsection
