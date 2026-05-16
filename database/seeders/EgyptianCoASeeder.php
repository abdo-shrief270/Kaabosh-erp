<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\NormalBalance;
use App\Domain\Accounting\Models\Account;
use Illuminate\Database\Seeder;

class EgyptianCoASeeder extends Seeder
{
    /**
     * Seed the Egyptian standard Chart of Accounts for a given tenant.
     *
     * Inserts accounts level by level so that parent_id references
     * can be resolved from previously inserted rows.
     */
    public function run(int $tenantId): void
    {
        $flatAccounts = $this->getFlatAccounts();
        $now = now();

        // Group by level for ordered insertion
        $byLevel = [];
        foreach ($flatAccounts as $account) {
            $byLevel[$account['level']][] = $account;
        }

        ksort($byLevel);

        // Code -> ID mapping for parent resolution
        $codeToId = [];

        foreach ($byLevel as $accounts) {
            $rows = [];
            foreach ($accounts as $account) {
                $parentId = null;
                if ($account['parent_code'] !== null) {
                    $parentId = $codeToId[$account['parent_code']] ?? null;
                }

                $rows[] = [
                    'tenant_id' => $tenantId,
                    'parent_id' => $parentId,
                    'code' => $account['code'],
                    'name_ar' => $account['name_ar'],
                    'name_en' => $account['name_en'],
                    'type' => $account['type']->value,
                    'normal_balance' => $account['normal_balance']->value,
                    'is_active' => true,
                    'is_group' => $account['is_group'],
                    'level' => $account['level'],
                    'description' => null,
                    'currency' => 'EGP',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert this level in batches
            foreach (array_chunk($rows, 50) as $chunk) {
                Account::query()->withoutGlobalScopes()->insert($chunk);
            }

            // Fetch inserted IDs for parent resolution of next levels
            $codes = array_column($accounts, 'code');
            $inserted = Account::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('code', $codes)
                ->pluck('id', 'code')
                ->toArray();

            $codeToId = array_merge($codeToId, $inserted);
        }
    }

    /**
     * Get the flat list of all Egyptian CoA accounts with parent_code references.
     *
     * @return array<int, array{code: string, name_ar: string, name_en: string, type: AccountType, normal_balance: NormalBalance, is_group: bool, level: int, parent_code: string|null}>
     */
    private function getFlatAccounts(): array
    {
        return [
            // ═══════════════════════════════════
            // 1000 - Assets
            // ═══════════════════════════════════
            ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 1, 'parent_code' => null],

            // 1100 - Current Assets
            ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '1000'],

            // 1110 - Cash & Banks
            ['code' => '1110', 'name_ar' => 'النقدية والبنوك', 'name_en' => 'Cash & Banks', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 3, 'parent_code' => '1100'],
            ['code' => '1111', 'name_ar' => 'الصندوق', 'name_en' => 'Cash on Hand', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1110'],
            ['code' => '1112', 'name_ar' => 'البنك', 'name_en' => 'Bank Accounts', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1110'],
            ['code' => '1113', 'name_ar' => 'شيكات تحت التحصيل', 'name_en' => 'Checks Under Collection', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1110'],

            // 1120 - Receivables
            ['code' => '1120', 'name_ar' => 'العملاء والمدينون', 'name_en' => 'Receivables', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 3, 'parent_code' => '1100'],
            ['code' => '1121', 'name_ar' => 'العملاء', 'name_en' => 'Accounts Receivable', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1120'],
            ['code' => '1122', 'name_ar' => 'أوراق القبض', 'name_en' => 'Notes Receivable', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1120'],
            ['code' => '1123', 'name_ar' => 'مدينون متنوعون', 'name_en' => 'Other Receivables', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1120'],

            // 1130 - Inventory
            ['code' => '1130', 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 3, 'parent_code' => '1100'],
            ['code' => '1131', 'name_ar' => 'بضاعة بالمخازن', 'name_en' => 'Goods in Warehouse', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1130'],
            ['code' => '1132', 'name_ar' => 'بضاعة بالطريق', 'name_en' => 'Goods in Transit', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1130'],

            // 1140 - Prepaid Expenses
            ['code' => '1140', 'name_ar' => 'مصروفات مقدمة', 'name_en' => 'Prepaid Expenses', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 3, 'parent_code' => '1100'],
            ['code' => '1141', 'name_ar' => 'إيجار مقدم', 'name_en' => 'Prepaid Rent', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1140'],
            ['code' => '1142', 'name_ar' => 'تأمين مقدم', 'name_en' => 'Prepaid Insurance', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 4, 'parent_code' => '1140'],

            // 1200 - Fixed Assets
            ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '1000'],
            ['code' => '1210', 'name_ar' => 'أراضي', 'name_en' => 'Land', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],
            ['code' => '1220', 'name_ar' => 'مباني', 'name_en' => 'Buildings', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],
            ['code' => '1230', 'name_ar' => 'سيارات', 'name_en' => 'Vehicles', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],
            ['code' => '1240', 'name_ar' => 'أثاث وتجهيزات', 'name_en' => 'Furniture & Fixtures', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],
            ['code' => '1250', 'name_ar' => 'أجهزة حاسب آلي', 'name_en' => 'Computer Equipment', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],
            // Special case: Accumulated Depreciation is Asset type but Credit normal balance
            ['code' => '1260', 'name_ar' => 'مجمع الإهلاك', 'name_en' => 'Accumulated Depreciation', 'type' => AccountType::Asset, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '1200'],

            // ═══════════════════════════════════
            // 2000 - Liabilities
            // ═══════════════════════════════════
            ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 1, 'parent_code' => null],

            // 2100 - Current Liabilities
            ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 2, 'parent_code' => '2000'],

            // 2110 - Payables
            ['code' => '2110', 'name_ar' => 'الموردون والدائنون', 'name_en' => 'Payables', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 3, 'parent_code' => '2100'],
            ['code' => '2111', 'name_ar' => 'الموردون', 'name_en' => 'Accounts Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2110'],
            ['code' => '2112', 'name_ar' => 'أوراق الدفع', 'name_en' => 'Notes Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2110'],
            ['code' => '2113', 'name_ar' => 'دائنون متنوعون', 'name_en' => 'Other Payables', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2110'],

            // 2120 - Accrued Expenses
            ['code' => '2120', 'name_ar' => 'المصروفات المستحقة', 'name_en' => 'Accrued Expenses', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 3, 'parent_code' => '2100'],
            ['code' => '2121', 'name_ar' => 'رواتب مستحقة', 'name_en' => 'Accrued Salaries', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2120'],
            ['code' => '2122', 'name_ar' => 'إيجار مستحق', 'name_en' => 'Accrued Rent', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2120'],

            // 2130 - Taxes Payable
            ['code' => '2130', 'name_ar' => 'الضرائب المستحقة', 'name_en' => 'Taxes Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 3, 'parent_code' => '2100'],
            ['code' => '2131', 'name_ar' => 'ضريبة القيمة المضافة', 'name_en' => 'VAT Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2130'],
            ['code' => '2132', 'name_ar' => 'ضريبة الدخل', 'name_en' => 'Income Tax Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2130'],
            ['code' => '2133', 'name_ar' => 'ضريبة كسب العمل', 'name_en' => 'Payroll Tax', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2130'],
            ['code' => '2134', 'name_ar' => 'تأمينات اجتماعية', 'name_en' => 'Social Insurance Payable', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 4, 'parent_code' => '2130'],

            // 2140 - Unearned Revenue
            ['code' => '2140', 'name_ar' => 'إيرادات مقدمة', 'name_en' => 'Unearned Revenue', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '2100'],

            // 2200 - Long-term Liabilities
            ['code' => '2200', 'name_ar' => 'الخصوم طويلة الأجل', 'name_en' => 'Long-term Liabilities', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 2, 'parent_code' => '2000'],
            ['code' => '2210', 'name_ar' => 'قروض طويلة الأجل', 'name_en' => 'Long-term Loans', 'type' => AccountType::Liability, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '2200'],

            // ═══════════════════════════════════
            // 3000 - Equity
            // ═══════════════════════════════════
            ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 1, 'parent_code' => null],
            ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 2, 'parent_code' => '3000'],

            // 3200 - Reserves
            ['code' => '3200', 'name_ar' => 'احتياطيات', 'name_en' => 'Reserves', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 2, 'parent_code' => '3000'],
            ['code' => '3210', 'name_ar' => 'احتياطي قانوني', 'name_en' => 'Legal Reserve', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '3200'],
            ['code' => '3220', 'name_ar' => 'احتياطي عام', 'name_en' => 'General Reserve', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '3200'],

            ['code' => '3300', 'name_ar' => 'أرباح (خسائر) مرحلة', 'name_en' => 'Retained Earnings', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 2, 'parent_code' => '3000'],
            ['code' => '3400', 'name_ar' => 'أرباح (خسائر) العام', 'name_en' => 'Current Year Earnings', 'type' => AccountType::Equity, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 2, 'parent_code' => '3000'],

            // ═══════════════════════════════════
            // 4000 - Revenue
            // ═══════════════════════════════════
            ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 1, 'parent_code' => null],

            // 4100 - Operating Revenue
            ['code' => '4100', 'name_ar' => 'إيرادات النشاط', 'name_en' => 'Operating Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 2, 'parent_code' => '4000'],
            ['code' => '4110', 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4100'],
            ['code' => '4120', 'name_ar' => 'إيرادات خدمات', 'name_en' => 'Service Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4100'],
            ['code' => '4130', 'name_ar' => 'إيرادات استشارات', 'name_en' => 'Consulting Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4100'],

            // 4200 - Other Revenue
            ['code' => '4200', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => true, 'level' => 2, 'parent_code' => '4000'],
            ['code' => '4210', 'name_ar' => 'إيرادات فوائد', 'name_en' => 'Interest Income', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4200'],
            ['code' => '4220', 'name_ar' => 'إيرادات إيجارات', 'name_en' => 'Rental Income', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4200'],
            ['code' => '4230', 'name_ar' => 'أرباح بيع أصول', 'name_en' => 'Gain on Asset Sale', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4200'],
            ['code' => '4240', 'name_ar' => 'إيرادات متنوعة', 'name_en' => 'Miscellaneous Income', 'type' => AccountType::Revenue, 'normal_balance' => NormalBalance::Credit, 'is_group' => false, 'level' => 3, 'parent_code' => '4200'],

            // ═══════════════════════════════════
            // 5000 - Expenses
            // ═══════════════════════════════════
            ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 1, 'parent_code' => null],

            // 5100 - Cost of Sales
            ['code' => '5100', 'name_ar' => 'تكلفة المبيعات', 'name_en' => 'Cost of Sales', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '5000'],
            ['code' => '5110', 'name_ar' => 'تكلفة البضاعة المباعة', 'name_en' => 'COGS', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5100'],
            ['code' => '5120', 'name_ar' => 'تكلفة الخدمات', 'name_en' => 'Cost of Services', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5100'],

            // 5200 - G&A Expenses
            ['code' => '5200', 'name_ar' => 'مصروفات إدارية وعمومية', 'name_en' => 'G&A Expenses', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '5000'],
            ['code' => '5210', 'name_ar' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5211', 'name_ar' => 'تأمينات اجتماعية - حصة الشركة', 'name_en' => 'Social Insurance - Employer', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5220', 'name_ar' => 'إيجارات', 'name_en' => 'Rent Expense', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5230', 'name_ar' => 'كهرباء ومياه', 'name_en' => 'Utilities', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5240', 'name_ar' => 'اتصالات', 'name_en' => 'Telecommunications', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5250', 'name_ar' => 'مصروفات سفر وانتقالات', 'name_en' => 'Travel & Transportation', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5260', 'name_ar' => 'صيانة وإصلاحات', 'name_en' => 'Maintenance & Repairs', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5270', 'name_ar' => 'مستلزمات مكتبية', 'name_en' => 'Office Supplies', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5280', 'name_ar' => 'إهلاك', 'name_en' => 'Depreciation Expense', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],
            ['code' => '5290', 'name_ar' => 'مصروفات متنوعة', 'name_en' => 'Miscellaneous Expenses', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5200'],

            // 5300 - Marketing Expenses
            ['code' => '5300', 'name_ar' => 'مصروفات تسويقية', 'name_en' => 'Marketing Expenses', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '5000'],
            ['code' => '5310', 'name_ar' => 'إعلانات', 'name_en' => 'Advertising', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5300'],
            ['code' => '5320', 'name_ar' => 'عمولات بيع', 'name_en' => 'Sales Commissions', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5300'],

            // 5400 - Finance Expenses
            ['code' => '5400', 'name_ar' => 'مصروفات تمويلية', 'name_en' => 'Finance Expenses', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => true, 'level' => 2, 'parent_code' => '5000'],
            ['code' => '5410', 'name_ar' => 'فوائد بنكية', 'name_en' => 'Bank Interest', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5400'],
            ['code' => '5420', 'name_ar' => 'مصاريف بنكية', 'name_en' => 'Bank Charges', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5400'],
            ['code' => '5430', 'name_ar' => 'خسائر فروق عملة', 'name_en' => 'Foreign Exchange Loss', 'type' => AccountType::Expense, 'normal_balance' => NormalBalance::Debit, 'is_group' => false, 'level' => 3, 'parent_code' => '5400'],
        ];
    }
}
