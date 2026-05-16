@extends('reports.layout')

@section('title')
    فاتورة / Invoice #{{ $invoice->invoice_number }}
@endsection

@section('date-range')
    {{ $invoice->date->format('Y-m-d') }}
@endsection

@section('content')
    {{-- Invoice Info & Client Details --}}
    <table style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td style="width: 50%; vertical-align: top; border: none; padding: 0;">
                    <div class="section-title">بيانات الفاتورة / Invoice Details</div>
                    <table style="margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td class="bold" style="width: 40%; border: none;">رقم الفاتورة / Invoice #</td>
                                <td class="number" style="border: none;">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="bold" style="border: none;">التاريخ / Date</td>
                                <td class="number" style="border: none;">{{ $invoice->date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td class="bold" style="border: none;">تاريخ الاستحقاق / Due Date</td>
                                <td class="number" style="border: none;">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="bold" style="border: none;">الحالة / Status</td>
                                <td style="border: none;">
                                    <span class="balanced-badge {{ in_array($invoice->status->value, ['paid']) ? 'balanced' : (in_array($invoice->status->value, ['overdue', 'cancelled']) ? 'unbalanced' : '') }}">
                                        {{ $invoice->status->labelAr() }} / {{ $invoice->status->label() }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="bold" style="border: none;">العملة / Currency</td>
                                <td style="border: none;">{{ $invoice->currency }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; border: none; padding: 0;">
                    <div class="section-title">بيانات العميل / Client Details</div>
                    <table style="margin-bottom: 0;">
                        <tbody>
                            @if($invoice->client)
                                <tr>
                                    <td class="bold" style="width: 40%; border: none;">الاسم / Name</td>
                                    <td style="border: none;">{{ $invoice->client->name }}</td>
                                </tr>
                                @if($invoice->client->trade_name)
                                <tr>
                                    <td class="bold" style="border: none;">الاسم التجاري / Trade Name</td>
                                    <td style="border: none;">{{ $invoice->client->trade_name }}</td>
                                </tr>
                                @endif
                                @if($invoice->client->tax_id)
                                <tr>
                                    <td class="bold" style="border: none;">الرقم الضريبي / Tax ID</td>
                                    <td class="number" style="border: none;">{{ $invoice->client->tax_id }}</td>
                                </tr>
                                @endif
                                @if($invoice->client->address || $invoice->client->city)
                                <tr>
                                    <td class="bold" style="border: none;">العنوان / Address</td>
                                    <td style="border: none;">{{ collect([$invoice->client->address, $invoice->client->city])->filter()->join(', ') }}</td>
                                </tr>
                                @endif
                                @if($invoice->client->phone)
                                <tr>
                                    <td class="bold" style="border: none;">الهاتف / Phone</td>
                                    <td class="number" style="border: none;">{{ $invoice->client->phone }}</td>
                                </tr>
                                @endif
                                @if($invoice->client->email)
                                <tr>
                                    <td class="bold" style="border: none;">البريد / Email</td>
                                    <td class="number" style="border: none;">{{ $invoice->client->email }}</td>
                                </tr>
                                @endif
                            @else
                                <tr>
                                    <td style="border: none;" colspan="2">—</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Line Items --}}
    <div class="section-title">بنود الفاتورة / Line Items</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">الوصف<br>Description</th>
                <th style="width: 10%;">الكمية<br>Qty</th>
                <th style="width: 15%;">سعر الوحدة<br>Unit Price</th>
                <th style="width: 10%;">نسبة الضريبة<br>VAT Rate</th>
                <th style="width: 15%;">ضريبة القيمة المضافة<br>VAT</th>
                <th style="width: 15%;">الإجمالي<br>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->lines as $index => $line)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="number">{{ number_format((float) $line->quantity, 2) }}</td>
                    <td class="number">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="number">{{ number_format((float) $line->vat_rate, 0) }}%</td>
                    <td class="number">{{ number_format((float) $line->vat_amount, 2) }}</td>
                    <td class="number">{{ number_format((float) $line->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">لا توجد بنود / No line items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals Summary --}}
    <table style="width: 50%; margin-right: 0; margin-left: auto;">
        <tbody>
            <tr class="subtotal-row">
                <td style="width: 60%;">المجموع الفرعي / Subtotal</td>
                <td class="number">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if((float) $invoice->discount_amount > 0)
            <tr>
                <td>الخصم / Discount</td>
                <td class="number" style="color: #c0392b;">({{ number_format((float) $invoice->discount_amount, 2) }})</td>
            </tr>
            @endif
            <tr>
                <td>ضريبة القيمة المضافة / VAT</td>
                <td class="number">{{ number_format((float) $invoice->vat_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>الإجمالي / Total</td>
                <td class="number">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td>المدفوع / Amount Paid</td>
                <td class="number" style="color: #27ae60;">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
            </tr>
            <tr class="grand-total-row">
                <td>المستحق / Balance Due</td>
                <td class="number">{{ number_format($invoice->balanceDue(), 2) }} {{ $invoice->currency }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Payment History --}}
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="section-title" style="margin-top: 20px;">سجل المدفوعات / Payment History</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">التاريخ<br>Date</th>
                    <th style="width: 20%;">المبلغ<br>Amount</th>
                    <th style="width: 20%;">طريقة الدفع<br>Method</th>
                    <th style="width: 15%;">المرجع<br>Reference</th>
                    <th style="width: 20%;">ملاحظات<br>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $index => $payment)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="number">{{ $payment->date ? $payment->date->format('Y-m-d') : '—' }}</td>
                        <td class="number">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="text-center">{{ $payment->method?->value ?? '—' }}</td>
                        <td>{{ $payment->reference ?? '—' }}</td>
                        <td>{{ $payment->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
        <div class="section-title" style="margin-top: 15px;">ملاحظات / Notes</div>
        <div style="padding: 8px; background-color: #f9f9f9; border: 1px solid #ddd; font-size: 9px; line-height: 1.6;">
            {{ $invoice->notes }}
        </div>
    @endif

    {{-- Terms --}}
    @if($invoice->terms)
        <div class="section-title" style="margin-top: 15px;">الشروط والأحكام / Terms & Conditions</div>
        <div style="padding: 8px; background-color: #f9f9f9; border: 1px solid #ddd; font-size: 9px; line-height: 1.6;">
            {{ $invoice->terms }}
        </div>
    @endif
@endsection
