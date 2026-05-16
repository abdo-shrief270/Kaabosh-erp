<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title')</title>
    <style>
        @page {
            margin: 20mm 15mm 25mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            direction: rtl;
            text-align: right;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }

        .header .tenant-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 3px;
        }

        .header .report-date {
            font-size: 10px;
            color: #7f8c8d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 6px 8px;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
        }

        td {
            padding: 4px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .number {
            text-align: left;
            direction: ltr;
            font-family: 'DejaVu Sans', monospace;
        }

        .bold {
            font-weight: bold;
        }

        .group-header {
            background-color: #ecf0f1;
            font-weight: bold;
            font-size: 10px;
        }

        .group-header td {
            border-bottom: 1px solid #bdc3c7;
        }

        .subtotal-row {
            background-color: #eaf2f8;
            font-weight: bold;
        }

        .subtotal-row td {
            border-top: 1px solid #aaa;
            border-bottom: 1px solid #aaa;
        }

        .total-row {
            background-color: #d5e8d4;
            font-weight: bold;
            font-size: 11px;
        }

        .total-row td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 6px 8px;
        }

        .grand-total-row {
            background-color: #2c3e50;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
        }

        .grand-total-row td {
            padding: 8px;
            border: none;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 15px;
            margin-bottom: 5px;
            padding: 5px;
            background-color: #eee;
            border-right: 4px solid #2c3e50;
        }

        .net-income-positive {
            background-color: #d5e8d4;
            font-weight: bold;
        }

        .net-income-negative {
            background-color: #f8d7da;
            font-weight: bold;
        }

        .balanced-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }

        .balanced {
            background-color: #d5e8d4;
            color: #155724;
        }

        .unbalanced {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .meta-info {
            font-size: 8px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="tenant-name">{{ $tenant->name_ar ?? $tenant->name ?? '' }}</div>
        <div class="report-title">@yield('title')</div>
        <div class="report-date">@yield('date-range')</div>
    </div>

    @yield('content')

    <div class="meta-info">
        @yield('title') | {{ $generatedAt }}
    </div>

    <div class="footer">
        Muhasebi Accounting System
    </div>
</body>
</html>
