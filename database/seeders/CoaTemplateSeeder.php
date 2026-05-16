<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Onboarding\Models\CoaTemplate;
use Illuminate\Database\Seeder;

class CoaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            $this->generalTemplate(),
            $this->retailTemplate(),
            $this->servicesTemplate(),
            $this->manufacturingTemplate(),
        ];

        foreach ($templates as $template) {
            CoaTemplate::query()->updateOrCreate(
                ['industry' => $template['industry']],
                $template,
            );
        }
    }

    private function generalTemplate(): array
    {
        return [
            'name_ar' => 'الدليل المحاسبي العام - مصر',
            'name_en' => 'Egyptian General Chart of Accounts',
            'industry' => 'general',
            'is_default' => true,
            'accounts' => [
                // ── Assets (1000) ──
                ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1110', 'name_ar' => 'النقدية والبنوك', 'name_en' => 'Cash & Banks', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1111', 'name_ar' => 'الصندوق', 'name_en' => 'Cash on Hand', 'type' => 'asset', 'parent_code' => '1110', 'normal_balance' => 'debit'],
                ['code' => '1112', 'name_ar' => 'البنك', 'name_en' => 'Bank Accounts', 'type' => 'asset', 'parent_code' => '1110', 'normal_balance' => 'debit'],
                ['code' => '1120', 'name_ar' => 'العملاء والمدينون', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1121', 'name_ar' => 'العملاء', 'name_en' => 'Trade Receivables', 'type' => 'asset', 'parent_code' => '1120', 'normal_balance' => 'debit'],
                ['code' => '1122', 'name_ar' => 'أوراق القبض', 'name_en' => 'Notes Receivable', 'type' => 'asset', 'parent_code' => '1120', 'normal_balance' => 'debit'],
                ['code' => '1130', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1140', 'name_ar' => 'مصروفات مقدمة', 'name_en' => 'Prepaid Expenses', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1150', 'name_ar' => 'ضريبة القيمة المضافة المدفوعة', 'name_en' => 'VAT Input', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1210', 'name_ar' => 'الأراضي', 'name_en' => 'Land', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1220', 'name_ar' => 'المباني', 'name_en' => 'Buildings', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1230', 'name_ar' => 'الأثاث والمعدات', 'name_en' => 'Furniture & Equipment', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1240', 'name_ar' => 'السيارات', 'name_en' => 'Vehicles', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1250', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'credit'],

                // ── Liabilities (2000) ──
                ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2110', 'name_ar' => 'الموردون والدائنون', 'name_en' => 'Accounts Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2120', 'name_ar' => 'أوراق الدفع', 'name_en' => 'Notes Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2130', 'name_ar' => 'مصروفات مستحقة', 'name_en' => 'Accrued Expenses', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2140', 'name_ar' => 'ضريبة القيمة المضافة المحصلة', 'name_en' => 'VAT Output', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2150', 'name_ar' => 'ضرائب مستحقة', 'name_en' => 'Taxes Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2200', 'name_ar' => 'الخصوم طويلة الأجل', 'name_en' => 'Long-term Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2210', 'name_ar' => 'قروض طويلة الأجل', 'name_en' => 'Long-term Loans', 'type' => 'liability', 'parent_code' => '2200', 'normal_balance' => 'credit'],

                // ── Equity (3000) ──
                ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3200', 'name_ar' => 'الاحتياطيات', 'name_en' => 'Reserves', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3300', 'name_ar' => 'الأرباح المحتجزة', 'name_en' => 'Retained Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3400', 'name_ar' => 'أرباح / خسائر العام', 'name_en' => 'Current Year Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],

                // ── Revenue (4000) ──
                ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '4100', 'name_ar' => 'إيرادات النشاط الرئيسي', 'name_en' => 'Operating Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4200', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4210', 'name_ar' => 'إيرادات فوائد', 'name_en' => 'Interest Income', 'type' => 'revenue', 'parent_code' => '4200', 'normal_balance' => 'credit'],

                // ── Expenses (5000) ──
                ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '5100', 'name_ar' => 'تكلفة المبيعات', 'name_en' => 'Cost of Sales', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5200', 'name_ar' => 'مصروفات إدارية وعمومية', 'name_en' => 'General & Admin Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5210', 'name_ar' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5220', 'name_ar' => 'إيجارات', 'name_en' => 'Rent Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5230', 'name_ar' => 'مصروفات كهرباء ومياه', 'name_en' => 'Utilities', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5240', 'name_ar' => 'مصروفات اتصالات', 'name_en' => 'Telecommunications', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5250', 'name_ar' => 'مصروفات صيانة', 'name_en' => 'Maintenance Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5260', 'name_ar' => 'مصروفات تأمين', 'name_en' => 'Insurance Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5270', 'name_ar' => 'مصروفات إهلاك', 'name_en' => 'Depreciation Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5300', 'name_ar' => 'مصروفات بيع وتسويق', 'name_en' => 'Selling & Marketing', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5400', 'name_ar' => 'مصروفات تمويلية', 'name_en' => 'Finance Costs', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5410', 'name_ar' => 'فوائد بنكية', 'name_en' => 'Bank Charges & Interest', 'type' => 'expense', 'parent_code' => '5400', 'normal_balance' => 'debit'],
            ],
        ];
    }

    private function retailTemplate(): array
    {
        return [
            'name_ar' => 'الدليل المحاسبي للتجارة والتجزئة',
            'name_en' => 'Retail Chart of Accounts',
            'industry' => 'retail',
            'is_default' => false,
            'accounts' => [
                // ── Assets ──
                ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1110', 'name_ar' => 'الصندوق', 'name_en' => 'Cash on Hand', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1111', 'name_ar' => 'صندوق نقاط البيع', 'name_en' => 'POS Cash Register', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1112', 'name_ar' => 'البنك', 'name_en' => 'Bank Accounts', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1120', 'name_ar' => 'العملاء', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1130', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1131', 'name_ar' => 'بضاعة بالمخازن', 'name_en' => 'Warehouse Stock', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1132', 'name_ar' => 'بضاعة بالفروع', 'name_en' => 'Branch Stock', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1133', 'name_ar' => 'بضاعة بالطريق', 'name_en' => 'Goods in Transit', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1140', 'name_ar' => 'مصروفات مقدمة', 'name_en' => 'Prepaid Expenses', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1150', 'name_ar' => 'ضريبة القيمة المضافة المدفوعة', 'name_en' => 'VAT Input', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1210', 'name_ar' => 'تجهيزات المحلات', 'name_en' => 'Store Fixtures', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1220', 'name_ar' => 'أجهزة نقاط البيع', 'name_en' => 'POS Equipment', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1230', 'name_ar' => 'سيارات النقل', 'name_en' => 'Delivery Vehicles', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1240', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'credit'],

                // ── Liabilities ──
                ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2110', 'name_ar' => 'الموردون', 'name_en' => 'Suppliers Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2120', 'name_ar' => 'مصروفات مستحقة', 'name_en' => 'Accrued Expenses', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2130', 'name_ar' => 'ضريبة القيمة المضافة المحصلة', 'name_en' => 'VAT Output', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2140', 'name_ar' => 'ضرائب مستحقة', 'name_en' => 'Taxes Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2150', 'name_ar' => 'إيرادات مؤجلة', 'name_en' => 'Deferred Revenue', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],

                // ── Equity ──
                ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3200', 'name_ar' => 'الأرباح المحتجزة', 'name_en' => 'Retained Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3300', 'name_ar' => 'أرباح / خسائر العام', 'name_en' => 'Current Year Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],

                // ── Revenue ──
                ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '4100', 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4110', 'name_ar' => 'مبيعات نقدية', 'name_en' => 'Cash Sales', 'type' => 'revenue', 'parent_code' => '4100', 'normal_balance' => 'credit'],
                ['code' => '4120', 'name_ar' => 'مبيعات آجلة', 'name_en' => 'Credit Sales', 'type' => 'revenue', 'parent_code' => '4100', 'normal_balance' => 'credit'],
                ['code' => '4130', 'name_ar' => 'مردودات المبيعات', 'name_en' => 'Sales Returns', 'type' => 'revenue', 'parent_code' => '4100', 'normal_balance' => 'debit'],
                ['code' => '4140', 'name_ar' => 'خصم مسموح به', 'name_en' => 'Sales Discounts', 'type' => 'revenue', 'parent_code' => '4100', 'normal_balance' => 'debit'],
                ['code' => '4200', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],

                // ── Expenses ──
                ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '5100', 'name_ar' => 'تكلفة البضاعة المباعة', 'name_en' => 'Cost of Goods Sold', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5110', 'name_ar' => 'مشتريات', 'name_en' => 'Purchases', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5120', 'name_ar' => 'مردودات المشتريات', 'name_en' => 'Purchase Returns', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'credit'],
                ['code' => '5130', 'name_ar' => 'مصاريف نقل المشتريات', 'name_en' => 'Freight In', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5200', 'name_ar' => 'مصروفات إدارية', 'name_en' => 'Admin Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5210', 'name_ar' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5220', 'name_ar' => 'إيجار المحلات', 'name_en' => 'Store Rent', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5230', 'name_ar' => 'مصروفات كهرباء ومياه', 'name_en' => 'Utilities', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5240', 'name_ar' => 'مصروفات تعبئة وتغليف', 'name_en' => 'Packaging Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5250', 'name_ar' => 'مصروفات إهلاك', 'name_en' => 'Depreciation Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5300', 'name_ar' => 'مصروفات بيع وتسويق', 'name_en' => 'Selling Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5310', 'name_ar' => 'مصروفات إعلان', 'name_en' => 'Advertising', 'type' => 'expense', 'parent_code' => '5300', 'normal_balance' => 'debit'],
                ['code' => '5320', 'name_ar' => 'مصروفات توصيل', 'name_en' => 'Delivery Expense', 'type' => 'expense', 'parent_code' => '5300', 'normal_balance' => 'debit'],
                ['code' => '5400', 'name_ar' => 'مصروفات تمويلية', 'name_en' => 'Finance Costs', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
            ],
        ];
    }

    private function servicesTemplate(): array
    {
        return [
            'name_ar' => 'الدليل المحاسبي لشركات الخدمات',
            'name_en' => 'Services Chart of Accounts',
            'industry' => 'services',
            'is_default' => false,
            'accounts' => [
                // ── Assets ──
                ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1110', 'name_ar' => 'الصندوق', 'name_en' => 'Cash on Hand', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1112', 'name_ar' => 'البنك', 'name_en' => 'Bank Accounts', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1120', 'name_ar' => 'العملاء', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1121', 'name_ar' => 'إيرادات مستحقة', 'name_en' => 'Accrued Revenue', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1130', 'name_ar' => 'مصروفات مقدمة', 'name_en' => 'Prepaid Expenses', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1140', 'name_ar' => 'ضريبة القيمة المضافة المدفوعة', 'name_en' => 'VAT Input', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1210', 'name_ar' => 'أجهزة كمبيوتر', 'name_en' => 'Computers & IT', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1220', 'name_ar' => 'أثاث مكتبي', 'name_en' => 'Office Furniture', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1230', 'name_ar' => 'سيارات', 'name_en' => 'Vehicles', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1240', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'credit'],

                // ── Liabilities ──
                ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2110', 'name_ar' => 'الدائنون', 'name_en' => 'Accounts Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2120', 'name_ar' => 'مصروفات مستحقة', 'name_en' => 'Accrued Expenses', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2130', 'name_ar' => 'ضريبة القيمة المضافة المحصلة', 'name_en' => 'VAT Output', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2140', 'name_ar' => 'ضرائب مستحقة', 'name_en' => 'Taxes Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2150', 'name_ar' => 'إيرادات مقدمة', 'name_en' => 'Unearned Revenue', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2160', 'name_ar' => 'تأمينات العملاء', 'name_en' => 'Client Deposits', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],

                // ── Equity ──
                ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3200', 'name_ar' => 'الأرباح المحتجزة', 'name_en' => 'Retained Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3300', 'name_ar' => 'أرباح / خسائر العام', 'name_en' => 'Current Year Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3400', 'name_ar' => 'حساب جاري الشركاء', 'name_en' => 'Partners Current Account', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],

                // ── Revenue ──
                ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '4100', 'name_ar' => 'إيرادات خدمات استشارية', 'name_en' => 'Consulting Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4110', 'name_ar' => 'إيرادات خدمات محاسبية', 'name_en' => 'Accounting Services', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4120', 'name_ar' => 'إيرادات خدمات ضريبية', 'name_en' => 'Tax Services', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4130', 'name_ar' => 'إيرادات خدمات مراجعة', 'name_en' => 'Audit Services', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4200', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],

                // ── Expenses ──
                ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '5100', 'name_ar' => 'تكلفة الخدمات', 'name_en' => 'Cost of Services', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5110', 'name_ar' => 'رواتب الفنيين', 'name_en' => 'Technical Staff Salaries', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5120', 'name_ar' => 'مصروفات مقاولين باطن', 'name_en' => 'Subcontractor Costs', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5200', 'name_ar' => 'مصروفات إدارية', 'name_en' => 'Admin Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5210', 'name_ar' => 'رواتب إدارية', 'name_en' => 'Admin Salaries', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5220', 'name_ar' => 'إيجار المكتب', 'name_en' => 'Office Rent', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5230', 'name_ar' => 'مصروفات كهرباء ومياه', 'name_en' => 'Utilities', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5240', 'name_ar' => 'مصروفات اتصالات وانترنت', 'name_en' => 'Telecom & Internet', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5250', 'name_ar' => 'مصروفات سفر وانتقال', 'name_en' => 'Travel Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5260', 'name_ar' => 'مصروفات تدريب', 'name_en' => 'Training Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5270', 'name_ar' => 'مصروفات إهلاك', 'name_en' => 'Depreciation Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5280', 'name_ar' => 'مصروفات تأمين', 'name_en' => 'Insurance Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5300', 'name_ar' => 'مصروفات تسويق', 'name_en' => 'Marketing Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5400', 'name_ar' => 'مصروفات تمويلية', 'name_en' => 'Finance Costs', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
            ],
        ];
    }

    private function manufacturingTemplate(): array
    {
        return [
            'name_ar' => 'الدليل المحاسبي للتصنيع',
            'name_en' => 'Manufacturing Chart of Accounts',
            'industry' => 'manufacturing',
            'is_default' => false,
            'accounts' => [
                // ── Assets ──
                ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1110', 'name_ar' => 'الصندوق', 'name_en' => 'Cash on Hand', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1112', 'name_ar' => 'البنك', 'name_en' => 'Bank Accounts', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1120', 'name_ar' => 'العملاء', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1130', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1131', 'name_ar' => 'مواد خام', 'name_en' => 'Raw Materials', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1132', 'name_ar' => 'إنتاج تحت التشغيل', 'name_en' => 'Work in Progress', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1133', 'name_ar' => 'منتجات تامة الصنع', 'name_en' => 'Finished Goods', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1134', 'name_ar' => 'قطع غيار ومهمات', 'name_en' => 'Spare Parts & Supplies', 'type' => 'asset', 'parent_code' => '1130', 'normal_balance' => 'debit'],
                ['code' => '1140', 'name_ar' => 'مصروفات مقدمة', 'name_en' => 'Prepaid Expenses', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1150', 'name_ar' => 'ضريبة القيمة المضافة المدفوعة', 'name_en' => 'VAT Input', 'type' => 'asset', 'parent_code' => '1100', 'normal_balance' => 'debit'],
                ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'parent_code' => '1000', 'normal_balance' => 'debit'],
                ['code' => '1210', 'name_ar' => 'الأراضي', 'name_en' => 'Land', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1220', 'name_ar' => 'المباني والمصانع', 'name_en' => 'Buildings & Factories', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1230', 'name_ar' => 'الآلات والمعدات', 'name_en' => 'Machinery & Equipment', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1240', 'name_ar' => 'سيارات ووسائل نقل', 'name_en' => 'Vehicles', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1250', 'name_ar' => 'أثاث ومعدات مكتبية', 'name_en' => 'Office Equipment', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'debit'],
                ['code' => '1260', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => 'asset', 'parent_code' => '1200', 'normal_balance' => 'credit'],

                // ── Liabilities ──
                ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2110', 'name_ar' => 'الموردون', 'name_en' => 'Suppliers Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2120', 'name_ar' => 'مصروفات مستحقة', 'name_en' => 'Accrued Expenses', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2130', 'name_ar' => 'ضريبة القيمة المضافة المحصلة', 'name_en' => 'VAT Output', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2140', 'name_ar' => 'ضرائب مستحقة', 'name_en' => 'Taxes Payable', 'type' => 'liability', 'parent_code' => '2100', 'normal_balance' => 'credit'],
                ['code' => '2200', 'name_ar' => 'الخصوم طويلة الأجل', 'name_en' => 'Long-term Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'normal_balance' => 'credit'],
                ['code' => '2210', 'name_ar' => 'قروض بنكية', 'name_en' => 'Bank Loans', 'type' => 'liability', 'parent_code' => '2200', 'normal_balance' => 'credit'],

                // ── Equity ──
                ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3200', 'name_ar' => 'الاحتياطي القانوني', 'name_en' => 'Legal Reserve', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3300', 'name_ar' => 'الأرباح المحتجزة', 'name_en' => 'Retained Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],
                ['code' => '3400', 'name_ar' => 'أرباح / خسائر العام', 'name_en' => 'Current Year Earnings', 'type' => 'equity', 'parent_code' => '3000', 'normal_balance' => 'credit'],

                // ── Revenue ──
                ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'parent_code' => null, 'normal_balance' => 'credit'],
                ['code' => '4100', 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4200', 'name_ar' => 'مردودات المبيعات', 'name_en' => 'Sales Returns', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'debit'],
                ['code' => '4300', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'normal_balance' => 'credit'],
                ['code' => '4310', 'name_ar' => 'إيرادات بيع مخلفات', 'name_en' => 'Scrap Sales', 'type' => 'revenue', 'parent_code' => '4300', 'normal_balance' => 'credit'],

                // ── Expenses ──
                ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'parent_code' => null, 'normal_balance' => 'debit'],
                ['code' => '5100', 'name_ar' => 'تكاليف الإنتاج', 'name_en' => 'Production Costs', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5110', 'name_ar' => 'مواد خام مستخدمة', 'name_en' => 'Raw Materials Used', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5120', 'name_ar' => 'أجور عمال الإنتاج', 'name_en' => 'Direct Labor', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5130', 'name_ar' => 'تكاليف صناعية غير مباشرة', 'name_en' => 'Manufacturing Overhead', 'type' => 'expense', 'parent_code' => '5100', 'normal_balance' => 'debit'],
                ['code' => '5131', 'name_ar' => 'صيانة الآلات', 'name_en' => 'Machine Maintenance', 'type' => 'expense', 'parent_code' => '5130', 'normal_balance' => 'debit'],
                ['code' => '5132', 'name_ar' => 'طاقة وقود', 'name_en' => 'Power & Fuel', 'type' => 'expense', 'parent_code' => '5130', 'normal_balance' => 'debit'],
                ['code' => '5133', 'name_ar' => 'إهلاك الآلات', 'name_en' => 'Machine Depreciation', 'type' => 'expense', 'parent_code' => '5130', 'normal_balance' => 'debit'],
                ['code' => '5200', 'name_ar' => 'مصروفات إدارية', 'name_en' => 'Admin Expenses', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5210', 'name_ar' => 'رواتب إدارية', 'name_en' => 'Admin Salaries', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5220', 'name_ar' => 'إيجارات', 'name_en' => 'Rent Expense', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5230', 'name_ar' => 'مصروفات عمومية', 'name_en' => 'General Expenses', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5240', 'name_ar' => 'مصروفات إهلاك إداري', 'name_en' => 'Admin Depreciation', 'type' => 'expense', 'parent_code' => '5200', 'normal_balance' => 'debit'],
                ['code' => '5300', 'name_ar' => 'مصروفات بيع وتوزيع', 'name_en' => 'Selling & Distribution', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5310', 'name_ar' => 'مصروفات نقل المبيعات', 'name_en' => 'Freight Out', 'type' => 'expense', 'parent_code' => '5300', 'normal_balance' => 'debit'],
                ['code' => '5320', 'name_ar' => 'عمولات مبيعات', 'name_en' => 'Sales Commissions', 'type' => 'expense', 'parent_code' => '5300', 'normal_balance' => 'debit'],
                ['code' => '5400', 'name_ar' => 'مصروفات تمويلية', 'name_en' => 'Finance Costs', 'type' => 'expense', 'parent_code' => '5000', 'normal_balance' => 'debit'],
                ['code' => '5410', 'name_ar' => 'فوائد بنكية', 'name_en' => 'Bank Interest', 'type' => 'expense', 'parent_code' => '5400', 'normal_balance' => 'debit'],
            ],
        ];
    }
}
