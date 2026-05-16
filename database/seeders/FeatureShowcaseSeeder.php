<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Cms\Models\FeatureShowcaseItem;
use App\Domain\Cms\Models\FeatureShowcaseSection;
use Illuminate\Database\Seeder;

/**
 * Curated feature showcase rendered on the public /features page.
 *
 * The catalog at config/features.php is the canonical list of plan-gated
 * modules; this seeder is the **marketing narrative** version — denser
 * descriptions, hidden / power-user capabilities included, and a tech-stack
 * section so technical buyers can see what's under the hood.
 *
 * Idempotent: keyed on `slug` per row so re-running refreshes copy without
 * duplicating sections.
 */
class FeatureShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $sortOrder => $section) {
            $items = $section['items'];
            unset($section['items']);

            $section['sort_order'] = $sortOrder;

            $row = FeatureShowcaseSection::updateOrCreate(
                ['slug' => $section['slug']],
                $section,
            );

            // Wipe + reseed items per section so re-running produces the
            // current canonical list without orphaned legacy rows.
            $row->items()->delete();
            foreach ($items as $itemSort => $item) {
                $item['section_id'] = $row->id;
                $item['sort_order'] = $itemSort;
                FeatureShowcaseItem::create($item);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sections(): array
    {
        return [
            // ─────────────────────────────────────────────────────────
            $this->section(
                slug: 'core-accounting',
                icon: 'i-lucide-book-open',
                accent: 'primary',
                titleEn: 'Core Accounting Suite',
                titleAr: 'مجموعة المحاسبة الأساسية',
                subtitleEn: 'Double-entry bookkeeping built around the way Egyptian accounting firms actually work — chart of accounts, journal entries, fiscal periods, and full GL posting on every transaction.',
                subtitleAr: 'محاسبة بالقيد المزدوج مبنية على الطريقة الفعلية لعمل مكاتب المحاسبة المصرية — دليل حسابات، قيود يومية، فترات مالية، وترحيل كامل للقيد العام عند كل معاملة.',
                items: $this->coreAccountingItems(),
            ),

            $this->section(
                slug: 'sales-receivables',
                icon: 'i-lucide-file-text',
                accent: 'success',
                titleEn: 'Sales & Receivables',
                titleAr: 'المبيعات والذمم المدينة',
                subtitleEn: 'From quote to cash — invoicing, recurring billing, payment matching, credit notes, and aged-receivables collections workflow.',
                subtitleAr: 'من العرض حتى التحصيل — فواتير، اشتراكات متكررة، مطابقة المدفوعات، إشعارات دائنة، وسير عمل تحصيل الديون.',
                items: $this->salesItems(),
            ),

            $this->section(
                slug: 'purchases-payables',
                icon: 'i-lucide-truck',
                accent: 'warning',
                titleEn: 'Purchases & Payables',
                titleAr: 'المشتريات والذمم الدائنة',
                subtitleEn: 'Vendor management, bill capture, expense tracking, and tightly-controlled payment workflow with approval gates and WHT handling.',
                subtitleAr: 'إدارة الموردين، فواتير الموردين، تتبع المصروفات، وسير عمل دفع منضبط مع بوابات اعتماد ومعالجة ضريبة الخصم من المنبع.',
                items: $this->purchasesItems(),
            ),

            $this->section(
                slug: 'compliance',
                icon: 'i-lucide-shield-check',
                accent: 'info',
                titleEn: 'Compliance & Egyptian Localization',
                titleAr: 'الامتثال والتوطين المصري',
                subtitleEn: 'Built for Egypt from day one. Direct ETA e-invoice integration, accurate VAT/WHT/Corporate Tax calculations, social-insurance rates, and Friday/Saturday weekends in audit checks.',
                subtitleAr: 'مصمم لمصر منذ اليوم الأول. تكامل مباشر مع منظومة الفاتورة الإلكترونية، حسابات دقيقة للقيمة المضافة وضريبة الخصم وضريبة الشركات، نسب التأمينات الاجتماعية، وعطلة الجمعة والسبت في فحوصات التدقيق.',
                items: $this->complianceItems(),
            ),

            $this->section(
                slug: 'banking-treasury',
                icon: 'i-lucide-landmark',
                accent: 'info',
                titleEn: 'Banking & Treasury',
                titleAr: 'البنوك والخزانة',
                subtitleEn: 'Multi-bank account management, statement reconciliation with auto-matching, FX revaluation, and OFX/CSV statement imports.',
                subtitleAr: 'إدارة حسابات بنكية متعددة، تسوية كشوف الحسابات بالمطابقة التلقائية، إعادة تقييم العملات الأجنبية، واستيراد كشوف بنكية بصيغة OFX/CSV.',
                items: $this->bankingItems(),
            ),

            $this->section(
                slug: 'fixed-assets-inventory',
                icon: 'i-lucide-boxes',
                accent: 'warning',
                titleEn: 'Fixed Assets & Inventory',
                titleAr: 'الأصول الثابتة والمخزون',
                subtitleEn: 'Asset register with automated depreciation runs, gain/loss-on-disposal posting, and stock movement tracking with reorder alerts.',
                subtitleAr: 'سجل أصول بحسابات إهلاك مؤتمتة، ترحيل أرباح/خسائر التصرف في الأصول، وتتبع حركة المخزون مع تنبيهات إعادة الطلب.',
                items: $this->fixedAssetsInventoryItems(),
            ),

            $this->section(
                slug: 'payroll-hr',
                icon: 'i-lucide-users-round',
                accent: 'primary',
                titleEn: 'Payroll & HR',
                titleAr: 'الرواتب والموارد البشرية',
                subtitleEn: 'Egyptian-rate payroll runs, full social-insurance calculations, leaves, loans, attendance, and downloadable payslips.',
                subtitleAr: 'مسيرات رواتب بأسعار مصرية، حسابات كاملة للتأمينات الاجتماعية، إجازات، سلف، حضور، وقسائم رواتب قابلة للتنزيل.',
                items: $this->payrollItems(),
            ),

            $this->section(
                slug: 'firm-services',
                icon: 'i-lucide-briefcase',
                accent: 'success',
                titleEn: 'Firm Services & Time Tracking',
                titleAr: 'خدمات المكتب وتتبع الوقت',
                subtitleEn: 'Engagement-based work for accounting firms — deliverables, working papers, timesheets, and one-click time-to-invoice billing.',
                subtitleAr: 'عمل قائم على الارتباطات لمكاتب المحاسبة — مخرجات، أوراق عمل، سجلات وقت، وفوترة الوقت بنقرة واحدة.',
                items: $this->firmServicesItems(),
            ),

            $this->section(
                slug: 'reporting-analytics',
                icon: 'i-lucide-bar-chart-3',
                accent: 'info',
                titleEn: 'Reporting & Analytics',
                titleAr: 'التقارير والتحليلات',
                subtitleEn: 'Trial balance, P&L, balance sheet, cash-flow statements, custom report builder, scheduled email delivery, and intelligent anomaly detection.',
                subtitleAr: 'ميزان المراجعة، قائمة الدخل، الميزانية، التدفقات النقدية، منشئ تقارير مخصصة، إرسال مجدول بالبريد، واكتشاف ذكي للشذوذ.',
                items: $this->reportingItems(),
            ),

            $this->section(
                slug: 'integrations-collaboration',
                icon: 'i-lucide-plug',
                accent: 'success',
                titleEn: 'Integrations & Client Collaboration',
                titleAr: 'التكاملات وتعاون العملاء',
                subtitleEn: 'Plug into Paymob, Fawry, Beon.chat, e-commerce platforms, and a dedicated client portal for invoices, documents, and messaging.',
                subtitleAr: 'تكامل مع Paymob وفوري وBeon.chat ومنصات التجارة الإلكترونية، مع بوابة عملاء مخصصة للفواتير والمستندات والمراسلات.',
                items: $this->integrationsItems(),
            ),

            $this->section(
                slug: 'platform-foundations',
                icon: 'i-lucide-layers',
                accent: 'primary',
                titleEn: 'Multi-tenant SaaS Foundations',
                titleAr: 'أساسيات منصة متعددة المستأجرين',
                subtitleEn: 'Isolation, RBAC, plans, add-ons, usage metering, and audit-grade activity logging — the spine of a serious SaaS.',
                subtitleAr: 'العزل، صلاحيات الأدوار، الخطط، الإضافات، قياس الاستخدام، وتسجيل أنشطة بمستوى التدقيق — العمود الفقري لمنصة SaaS جادة.',
                items: $this->platformItems(),
            ),

            $this->section(
                slug: 'hidden-power',
                icon: 'i-lucide-sparkles',
                accent: 'warning',
                titleEn: 'Hidden & Power Features',
                titleAr: 'مزايا خفية ومتقدمة',
                subtitleEn: 'The capabilities most accounting platforms ship as paid add-ons — included, on every tier, on by default.',
                subtitleAr: 'القدرات التي تبيعها معظم منصات المحاسبة كإضافات مدفوعة — مشمولة هنا، في كل الباقات، ومُفعّلة افتراضياً.',
                items: $this->hiddenItems(),
            ),

            $this->section(
                slug: 'security-reliability',
                icon: 'i-lucide-lock',
                accent: 'danger',
                titleEn: 'Security & Reliability',
                titleAr: 'الأمان والموثوقية',
                subtitleEn: 'Two-factor authentication, IP allow-listing for SuperAdmins, idempotency on every state-changing endpoint, GL reconciliation safety nets, and bcmath precision on every cent.',
                subtitleAr: 'مصادقة ثنائية، قوائم IP مسموح بها للمشرفين، حماية من تكرار الطلبات على كل عملية تغيير، شبكات أمان لمطابقة القيد العام، ودقة bcmath في كل قرش.',
                items: $this->securityItems(),
            ),

            $this->section(
                slug: 'tech-stack',
                icon: 'i-lucide-cpu',
                accent: 'info',
                titleEn: 'Technology Stack',
                titleAr: 'التقنيات المستخدمة',
                subtitleEn: 'Modern, audited foundations: Laravel 13, Filament 5, Nuxt 4, PostgreSQL, Redis, Vue 3.5 + Vite, Tailwind v4. No clever proprietary lock-in.',
                subtitleAr: 'أسس حديثة وموثقة: Laravel 13، Filament 5، Nuxt 4، PostgreSQL، Redis، Vue 3.5 + Vite، Tailwind v4. بدون احتكار تقني.',
                items: $this->techStackItems(),
            ),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Section bodies
    // ─────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    private function coreAccountingItems(): array
    {
        return [
            $this->item('i-lucide-list-tree', 'Chart of Accounts (Egyptian template)', 'دليل الحسابات (قالب مصري)',
                'Pre-seeded with the canonical Egyptian COA — five-level hierarchy, Arabic + English labels, and account-type rules that drive automatic GL posting on every invoice, bill, payment, and adjustment.',
                'محمّل مسبقاً بدليل الحسابات المصري المعتمد — تسلسل من خمس مستويات، تسميات عربية وإنجليزية، وقواعد على نوع الحساب تقود الترحيل التلقائي للقيد عند كل فاتورة ودفعة وتسوية.'),
            $this->item('i-lucide-book-open', 'Journal Entries with Approval Gates', 'القيود اليومية مع بوابات الاعتماد',
                'Manual journals with line-level cost-centers, multi-currency support, and configurable approval thresholds — entries above an amount must be approved before they post to the GL.',
                'قيود يدوية مع مراكز تكلفة على مستوى البند، دعم متعدد العملات، وعتبات اعتماد قابلة للضبط — القيود فوق مبلغ معين تتطلب اعتماداً قبل ترحيلها للقيد العام.'),
            $this->item('i-lucide-repeat', 'Recurring Journal Entries', 'القيود المتكررة',
                'Schedule rent, depreciation, and prepaid-expense reversals to post automatically on a defined cadence — daily, weekly, monthly, quarterly, or yearly.',
                'جدولة الإيجار والإهلاك وعكس المصروفات المدفوعة مقدماً للترحيل تلقائياً بإيقاع محدد — يومي أو أسبوعي أو شهري أو ربع سنوي أو سنوي.'),
            $this->item('i-lucide-calendar-days', 'Fiscal Calendar with Period Lock', 'التقويم المالي مع قفل الفترات',
                'Define your fiscal year, close periods to prevent retroactive postings, and lock entries against tampering. The lock is enforced at the service layer for create/update/post/reverse.',
                'حدد سنتك المالية، أقفل الفترات لمنع الترحيل بأثر رجعي، وثبّت القيود ضد العبث. القفل مُنفّذ في طبقة الخدمة لإنشاء/تحديث/ترحيل/عكس القيود.'),
            $this->item('i-lucide-layers', 'Cost Centers', 'مراكز التكلفة',
                'Tag every journal-entry line and invoice with a cost center to slice P&L by department, branch, project, or client portfolio.',
                'وسم كل بند قيد يومي وفاتورة بمركز تكلفة لتقسيم قائمة الدخل حسب القسم أو الفرع أو المشروع أو محفظة العملاء.'),
            $this->item('i-lucide-target', 'Budgets & Variance Tracking', 'الموازنات وتتبع الانحرافات',
                'Set monthly and YTD budgets per account, watch month-over-month variance and percentage burn, with overage alerts.',
                'حدد موازنات شهرية ومن بداية السنة لكل حساب، راقب الانحراف الشهري ونسبة الاستهلاك، مع تنبيهات تجاوز.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function salesItems(): array
    {
        return [
            $this->item('i-lucide-users', 'Client Roster with Contact Hub', 'سجل العملاء مع مركز التواصل',
                'Per-client tax ID, commercial register, contact persons, activity type, and bilingual notes — all queryable for ETA submissions and statements.',
                'لكل عميل: رقم ضريبي، سجل تجاري، أشخاص اتصال، نوع نشاط، وملاحظات ثنائية اللغة — كلها قابلة للاستعلام لإرسال ETA وكشوف الحسابات.'),
            $this->item('i-lucide-package', 'Per-Client Product Catalog', 'كتالوج منتجات لكل عميل',
                'Save bespoke service packages per client (e.g. "Monthly bookkeeping — ABC Co"), and the invoice picker auto-fills price + revenue account when selected.',
                'احفظ باقات خدمات مخصصة لكل عميل (مثل "محاسبة شهرية - شركة س"), ومنتقي الفاتورة يعبئ السعر وحساب الإيراد تلقائياً عند الاختيار.'),
            $this->item('i-lucide-file-text', 'Invoicing with Line-level VAT', 'الفواتير مع ضريبة على مستوى البند',
                'Per-line VAT and discount, multi-currency, customizable layouts, with one-click PDF + ETA submission once the customer confirms.',
                'ضريبة وخصم على مستوى البند، عملات متعددة، تخطيطات قابلة للتخصيص، مع إصدار PDF بنقرة واحدة + إرسال لـ ETA بعد تأكيد العميل.'),
            $this->item('i-lucide-repeat', 'Recurring Invoices', 'الفواتير المتكررة',
                'For monthly retainers, subscriptions, and service packages — define cadence, end date, auto-send, and the SPA shows next-run countdown.',
                'لرسوم الاحتفاظ الشهرية والاشتراكات وباقات الخدمات — حدد التكرار وتاريخ الانتهاء والإرسال التلقائي، ويعرض التطبيق العد التنازلي للتشغيل القادم.'),
            $this->item('i-lucide-wallet', 'Payments Received with Auto-Match', 'المتحصلات مع المطابقة التلقائية',
                'Record cash/bank/check payments with automatic invoice matching by amount + reference, and partial-payment support out of the box.',
                'سجّل المدفوعات النقدية/البنكية/الشيكات مع مطابقة تلقائية بالفاتورة بناءً على المبلغ + المرجع، ودعم المدفوعات الجزئية افتراضياً.'),
            $this->item('i-lucide-receipt', 'Credit Notes', 'إشعارات دائنة',
                'Issue credit notes against any posted invoice — the system reverses the original GL impact and re-aligns AR balances correctly.',
                'إصدار إشعارات دائنة على أي فاتورة مُرحّلة — النظام يعكس الأثر الأصلي على القيد العام ويعيد محاذاة أرصدة الذمم المدينة بشكل صحيح.'),
            $this->item('i-lucide-clock-alert', 'Aged-Receivables Collections', 'تحصيل الذمم العمرية',
                'Aged-receivables buckets (0/30/60/90/+) with one-click reminder sequences, write-offs, and dunning history per client.',
                'فئات أعمار الديون (0/30/60/90/+) مع تذكيرات بنقرة واحدة، إعفاءات، وسجل مطالبة لكل عميل.'),
            $this->item('i-lucide-message-circle', 'Client Portal Access', 'وصول بوابة العملاء', null, null,
                'Magic-link invitations let clients view their invoices, download statements, upload documents, and message your firm directly — no shared inbox required.',
                'دعوات بروابط سحرية تسمح للعملاء بمشاهدة فواتيرهم وتنزيل كشوف الحسابات ورفع المستندات ومراسلة مكتبك مباشرة — بدون صندوق بريد مشترك.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function purchasesItems(): array
    {
        return [
            $this->item('i-lucide-truck', 'Vendor Master with Bilingual Names', 'سجل الموردين بأسماء ثنائية اللغة',
                'Vendor records carry name_en/name_ar, tax ID, payment terms, default WHT rates, and per-vendor product catalog for fast bill entry.',
                'سجلات الموردين تحمل اسم بالعربية والإنجليزية، رقم ضريبي، شروط الدفع، نسب خصم منبع افتراضية، وكتالوج منتجات لكل مورد لإدخال فواتير سريع.'),
            $this->item('i-lucide-file-input', 'Bills with WHT, VAT, and Discount', 'فواتير الموردين مع خصم منبع وضريبة وخصم',
                'Capture bills with line-level VAT/WHT/discount, attach scanned PDFs, and auto-post the matching JE on approval.',
                'سجّل فواتير الموردين مع ضريبة/خصم منبع/خصم على مستوى البند، أرفق ملفات PDF ممسوحة، وقم بترحيل القيد المطابق تلقائياً عند الاعتماد.'),
            $this->item('i-lucide-banknote', 'Bill Payments with Approval Gates', 'مدفوعات الموردين مع بوابات الاعتماد',
                'Record vendor payments with method (cash/bank/check), reference number, and configurable approval threshold — payments above the limit need a second signoff.',
                'سجّل مدفوعات الموردين مع طريقة الدفع (نقدي/بنكي/شيك)، رقم المرجع، وعتبة اعتماد قابلة للضبط — المدفوعات فوق الحد تتطلب توقيعاً ثانياً.'),
            $this->item('i-lucide-credit-card', 'Expenses with Receipt Storage', 'المصروفات مع تخزين الإيصالات',
                'Quick expense capture from a phone — pick category, attach receipt photo, route to an expense report, and reimburse from payroll or directly.',
                'إدخال مصروفات سريع من الهاتف — اختر الفئة، أرفق صورة الإيصال، أدرج المصروف في تقرير، واسترد من الراتب أو مباشرة.'),
            $this->item('i-lucide-file-spreadsheet', 'Expense Reports', 'تقارير المصروفات',
                'Bundle multiple expenses into a single submitted report with workflow approval and reimbursement tracking.',
                'اجمع عدة مصروفات في تقرير واحد مع سير اعتماد وتتبع الاسترداد.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function complianceItems(): array
    {
        return [
            $this->item('i-lucide-scan-line', 'ETA E-Invoice Direct Integration', 'تكامل مباشر مع الفاتورة الإلكترونية', 'Egyptian Tax Authority', 'مصلحة الضرائب',
                'Submit invoices to the Egyptian Tax Authority API directly from the platform. We sign payloads, store the UUID + status callbacks, render QR codes, and expose a reconciliation tool that compares your local records against the ETA portal.',
                'أرسل الفواتير لـ API مصلحة الضرائب المصرية مباشرة من المنصة. نوقّع الحمولات، نخزّن UUID + ردود الحالة، نعرض رموز QR، ونوفّر أداة مطابقة تقارن سجلاتك المحلية ببوابة الضرائب.'),
            $this->item('i-lucide-barcode', 'GS1/EGS Item Codes', 'أكواد الأصناف GS1/EGS',
                'Manage your ETA-registered item codes (EGS or GS1), tax types, and unit codes from the same screen the invoice picker reads from.',
                'إدارة أكواد الأصناف المسجلة في ETA (EGS أو GS1) وأنواع الضرائب وأكواد الوحدات من نفس الشاشة التي يقرأ منها منتقي الفاتورة.'),
            $this->item('i-lucide-percent', 'VAT Returns (Form 10)', 'إقرارات ضريبة القيمة المضافة (نموذج 10)',
                'Auto-compute output VAT, input VAT, credit-notes adjustments, and a clean VAT-by-rate breakdown — ready to file Form 10 with bcmath precision (no float drift).',
                'احتساب تلقائي لضريبة المخرجات والمدخلات وتعديلات إشعارات الدائنة، وتفصيل ضريبة بحسب النسبة — جاهز لتقديم نموذج 10 بدقة bcmath (بدون انحراف عشري).'),
            $this->item('i-lucide-receipt-text', 'WHT Certificates', 'شهادات الخصم من المنبع',
                'Withholding tax tracked per bill line, certificates generated per vendor per period, and the matching JE posts to your WHT-payable account.',
                'ضريبة الخصم من المنبع مُتتبعة على مستوى بند الفاتورة، شهادات تُولَّد لكل مورد لكل فترة، والقيد المطابق يُرحَّل لحساب ضريبة الخصم المستحقة.'),
            $this->item('i-lucide-building-2', 'Corporate Tax Calculation', 'حساب ضريبة الشركات',
                'Adjustment-aware corporate tax — start from accounting profit, layer in tax-deductible/non-deductible adjustments and loss carry-forwards, generate a working file ready for filing.',
                'ضريبة شركات مع إدراك للتسويات — ابدأ من الربح المحاسبي، أضف التسويات القابلة وغير القابلة للخصم وترحيل الخسائر، وأنتج ملف عمل جاهز للتقديم.'),
            $this->item('i-lucide-shield', 'Egyptian Social Insurance Rates', 'نسب التأمينات الاجتماعية المصرية',
                'Built-in Egyptian social-insurance rate tables (employee + employer share), updated to current law, applied automatically inside payroll runs.',
                'جداول نسب التأمينات الاجتماعية المصرية مدمجة (حصة الموظف + صاحب العمل)، محدثة حسب القانون الحالي، ومُطبقة تلقائياً داخل مسيرات الرواتب.'),
            $this->item('i-lucide-globe', 'Bilingual AR/EN with Full RTL', 'دعم ثنائي اللغة عربي/إنجليزي مع RTL كامل',
                'Every screen, every email, every report — Arabic-first design with first-class English. Switch on demand. Numbers, dates, and currencies localize correctly.',
                'كل شاشة، كل بريد، كل تقرير — تصميم يبدأ بالعربية مع دعم إنجليزي بالدرجة الأولى. التبديل عند الطلب. الأرقام والتواريخ والعملات تتعرّب بشكل صحيح.'),
            $this->item('i-lucide-calendar-x', 'Friday/Saturday Weekend Awareness', 'إدراك عطلة الجمعة والسبت',
                'Weekend-entry detection in audit checks correctly treats Friday/Saturday as the Egyptian weekend (not Saturday/Sunday).',
                'كشف القيود في عطلة نهاية الأسبوع يعامل الجمعة/السبت كعطلة مصرية بشكل صحيح (وليس السبت/الأحد).'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function bankingItems(): array
    {
        return [
            $this->item('i-lucide-landmark', 'Multi-Bank Accounts', 'حسابات بنكية متعددة',
                'Track unlimited bank accounts per tenant, each with its own currency, opening balance, and linked GL account.',
                'تتبع عدد غير محدود من الحسابات البنكية لكل منشأة، لكل حساب عملته الخاصة ورصيد افتتاحي وحساب قيد عام مرتبط.'),
            $this->item('i-lucide-git-compare', 'Bank Reconciliation', 'التسوية البنكية',
                'Upload an OFX or CSV statement, the system fuzzy-matches lines against your ledger, and you confirm or split as needed. Variance is shown live.',
                'ارفع كشف بنكي بصيغة OFX أو CSV، يقوم النظام بالمطابقة التقريبية مع دفتر الأستاذ، وأنت تؤكد أو تقسّم حسب الحاجة. الفروقات تظهر فوراً.'),
            $this->item('i-lucide-zap', 'Auto-Reconciliation Suggestions', 'اقتراحات تسوية تلقائية',
                'Statement lines learn from your manual matches — vendor + amount + reference patterns trigger one-click "Apply" on similar lines next time.',
                'بنود كشف الحساب تتعلم من المطابقات اليدوية — أنماط المورد + المبلغ + المرجع تُفعّل زر "تطبيق" بنقرة واحدة على البنود المشابهة في المرة القادمة.'),
            $this->item('i-lucide-arrow-right-left', 'FX Revaluation', 'إعادة تقييم العملات',
                'Period-end revaluation of foreign-currency balances against closing rates, with automatic unrealized gain/loss postings.',
                'إعادة تقييم الأرصدة بالعملات الأجنبية في نهاية الفترة مقابل أسعار الإقفال، مع ترحيل تلقائي للأرباح/الخسائر غير المحققة.'),
            $this->item('i-lucide-coins', 'Multi-Currency with Daily Rates', 'متعدد العملات بأسعار يومية',
                'Hold balances in EGP, USD, EUR, SAR, AED — daily exchange rates seeded with manual override, applied at transaction time and re-applied at period close.',
                'احتفظ بالأرصدة بالجنيه المصري والدولار واليورو والريال والدرهم — أسعار صرف يومية محملة مع إمكانية التعديل اليدوي، تُطبَّق وقت المعاملة وتُعاد عند إقفال الفترة.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fixedAssetsInventoryItems(): array
    {
        return [
            $this->item('i-lucide-box', 'Fixed Asset Register', 'سجل الأصول الثابتة',
                'Per-asset cost, useful-life, salvage value, and current net book value visible at a glance.',
                'لكل أصل: التكلفة، العمر الإنتاجي، قيمة الخردة، وصافي القيمة الدفترية الحالي ظاهرة بنظرة واحدة.'),
            $this->item('i-lucide-trending-down', 'Automated Depreciation Runs', 'مسيرات إهلاك مؤتمتة',
                'Monthly straight-line or declining-balance runs that post the depreciation expense + accumulated depreciation entries automatically and roll into the asset NBV.',
                'مسيرات شهرية بطريقة القسط الثابت أو المتناقص تقوم بترحيل قيود مصروف الإهلاك + مجمع الإهلاك تلقائياً وتنعكس على صافي القيمة الدفترية.'),
            $this->item('i-lucide-archive-x', 'Asset Disposals with Gain/Loss', 'استبعاد الأصول مع ربح/خسارة',
                'Sale, write-off, donation — the disposal flow computes gain/loss against NBV and posts the matching journal entry with proper account routing.',
                'بيع، استبعاد، تبرع — مسار الاستبعاد يحسب الربح/الخسارة مقابل صافي القيمة الدفترية ويرحّل القيد المطابق بتوجيه حسابات صحيح.'),
            $this->item('i-lucide-package', 'Inventory with Categories', 'المخزون بالفئات',
                'SKU, units, prices, reorder levels, valuation method (FIFO / weighted average) — and a category tree to organize a thousand-product catalog.',
                'كود الصنف، الوحدات، الأسعار، حدود إعادة الطلب، طريقة التقييم (FIFO / المتوسط المرجح) — وشجرة فئات لتنظيم كتالوج بألف صنف.'),
            $this->item('i-lucide-arrow-down-up', 'Stock Movements', 'حركات المخزون',
                'Stock-in, stock-out, transfers, and adjustments — every movement linked back to its source document (bill, sale, manual adjustment).',
                'إدخال، صرف، تحويل، تسوية — كل حركة مرتبطة بمستندها الأصلي (فاتورة شراء، بيع، تسوية يدوية).'),
            $this->item('i-lucide-package-x', 'Reorder Alerts', 'تنبيهات إعادة الطلب',
                'Real-time alerts when on-hand stock drops at or below the reorder level, with the count surfaced in the dashboard.',
                'تنبيهات فورية عند هبوط المخزون المتاح إلى/أو أسفل حد إعادة الطلب، مع عرض العدد في لوحة التحكم.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function payrollItems(): array
    {
        return [
            $this->item('i-lucide-users-round', 'Employee Master', 'سجل الموظفين',
                'Hire date, base salary, job title, department, and social-insurance enrollment per employee.',
                'تاريخ التعيين، الراتب الأساسي، المسمى الوظيفي، القسم، وتسجيل التأمينات الاجتماعية لكل موظف.'),
            $this->item('i-lucide-calendar-clock', 'Monthly Payroll Runs', 'مسيرات الرواتب الشهرية',
                'Calculate, approve, mark-paid — a clean status flow with cumulative totals (gross, SI, tax, deductions, net) on a single screen.',
                'احسب، اعتمد، حدد كمدفوع — مسار حالة نظيف مع إجماليات تراكمية (إجمالي، تأمينات، ضرائب، خصومات، صافي) على شاشة واحدة.'),
            $this->item('i-lucide-receipt', 'Payslips (PDF)', 'قسائم الرواتب (PDF)',
                'Bilingual payslip PDF per employee per period, downloadable from the run detail page.',
                'قسيمة راتب PDF ثنائية اللغة لكل موظف لكل فترة، قابلة للتنزيل من صفحة تفاصيل المسير.'),
            $this->item('i-lucide-sliders', 'Salary Components', 'مكونات الراتب',
                'Reusable allowances and deductions (transport, housing, late penalty) attachable per employee or for the whole run.',
                'بدلات وخصومات قابلة لإعادة الاستخدام (مواصلات، سكن، جزاء تأخير) تُربط بكل موظف أو على مستوى المسير كاملاً.'),
            $this->item('i-lucide-hand-coins', 'Loans & Advances', 'سلف وقروض',
                'Track principal, monthly installment, and outstanding balance — installments are auto-deducted on the next payroll run until cleared.',
                'تتبع الأصل، القسط الشهري، والرصيد المتبقي — الأقساط تُخصم تلقائياً في مسير الرواتب القادم حتى السداد.'),
            $this->item('i-lucide-plane', 'Leave Requests', 'طلبات الإجازات',
                'Annual / sick / unpaid leave with approval workflow, day-count enforcement, and integration with attendance.',
                'إجازات سنوية / مرضية / بدون أجر مع سير اعتماد، إنفاذ عدد الأيام، وتكامل مع الحضور.'),
            $this->item('i-lucide-clock-3', 'Attendance', 'الحضور والانصراف',
                'Daily check-in / check-out records with cumulative hours, ready for payroll inclusion.',
                'سجلات تسجيل دخول/خروج يومية مع ساعات تراكمية، جاهزة للإدراج في الرواتب.'),
            $this->item('i-lucide-shield-check', 'Social Insurance Reports', 'تقارير التأمينات الاجتماعية',
                'Monthly contribution reports broken down by employee + employer share, ready for filing.',
                'تقارير اشتراكات شهرية موزّعة على حصة الموظف + صاحب العمل، جاهزة للتقديم.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function firmServicesItems(): array
    {
        return [
            $this->item('i-lucide-briefcase', 'Engagements', 'الارتباطات',
                'Multi-client engagements (audit, bookkeeping, advisory) with reference codes, type, status, and per-engagement budget.',
                'ارتباطات متعددة العملاء (تدقيق، محاسبة، استشارات) مع أكواد مرجعية، نوع، حالة، وموازنة لكل ارتباط.'),
            $this->item('i-lucide-check-circle-2', 'Deliverables Tracking', 'تتبع المخرجات',
                'Per-engagement deliverable list with due dates, status (pending/in-progress/completed/overdue), and overdue highlighting.',
                'قائمة مخرجات لكل ارتباط مع تواريخ استحقاق، حالة (قيد الانتظار/قيد العمل/مكتمل/متأخر), وتمييز التأخر.'),
            $this->item('i-lucide-file-stack', 'Working Papers Library', 'مكتبة أوراق العمل',
                'Audit-trail working papers attachable to engagements, organized by type, with versioned uploads.',
                'أوراق عمل قابلة للإرفاق بالارتباطات، منظمة حسب النوع، مع رفع نسخ مُؤرشفة.'),
            $this->item('i-lucide-clock-4', 'Timesheets', 'سجلات الوقت',
                'Time entries per client + task + date with billable/non-billable flag, approval workflow, and a real-time start/stop timer.',
                'إدخالات وقت لكل عميل + مهمة + تاريخ مع علامة قابلة/غير قابلة للفوترة، سير اعتماد، ومؤقت تشغيل/إيقاف فوري.'),
            $this->item('i-lucide-file-clock', 'Time-to-Invoice Billing', 'فوترة الوقت',
                'Preview unbilled hours per client over a date range, then generate a single invoice that bundles every approved entry into line items priced at the entry\'s hourly rate.',
                'استعرض الساعات غير المفوترة لكل عميل ضمن نطاق تاريخي، ثم أنشئ فاتورة واحدة تجمع كل القيود المعتمدة إلى بنود مسعّرة بسعر ساعة كل قيد.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function reportingItems(): array
    {
        return [
            $this->item('i-lucide-bar-chart-3', 'Standard Financial Reports', 'التقارير المالية الأساسية',
                'Trial balance, profit & loss, balance sheet, cash-flow statement, AR aging, AP aging — bilingual headers, period selector, and export to PDF/Excel.',
                'ميزان المراجعة، قائمة الدخل، الميزانية، التدفقات النقدية، أعمار الذمم المدينة والدائنة — رؤوس ثنائية اللغة، اختيار الفترة، وتصدير PDF/Excel.'),
            $this->item('i-lucide-sliders-horizontal', 'Custom Report Builder', 'منشئ تقارير مخصصة',
                'Drag-and-drop columns, filters, groupings — save as templates and share within the firm.',
                'سحب وإفلات للأعمدة والفلاتر والتجميعات — احفظها كقوالب وشاركها داخل المكتب.'),
            $this->item('i-lucide-calendar-clock', 'Scheduled Email Delivery', 'تسليم مجدول بالبريد',
                'Set any report to email itself daily/weekly/monthly to chosen recipients — Egyptian fiscal periods supported.',
                'اضبط أي تقرير ليُرسل ذاتياً بالبريد يومياً/أسبوعياً/شهرياً لمستلمين محددين — مع دعم الفترات المالية المصرية.'),
            $this->item('i-lucide-alert-octagon', 'Anomaly Detection (7 detectors)', 'اكتشاف الشذوذ (7 كاشفات)', 'Built-in', 'مدمج',
                'Out-of-the-box anomaly detection across duplicate invoices, unusual amounts (3σ outliers), missing invoice/JE sequences, weekend entries, round-number bias, unusual vendor payments, and dormant-account activity.',
                'اكتشاف شذوذ جاهز يغطي الفواتير المكررة، المبالغ الشاذة (3σ), أرقام فواتير/قيود مفقودة، قيود في عطلة الأسبوع، تحيز للأرقام المقربة، مدفوعات موردين شاذة، ونشاط حسابات خاملة.'),
            $this->item('i-lucide-shield-check', 'Audit Log (Compliance)', 'سجل التدقيق (الامتثال)',
                'Every model change recorded with actor, timestamp, before/after values via Spatie Activitylog. Filter by entity, user, or date range from the SuperAdmin panel.',
                'كل تغيير في النموذج مسجل مع المُنفّذ، الطابع الزمني، القيم قبل/بعد عبر Spatie Activitylog. فلترة حسب الكيان أو المستخدم أو نطاق التاريخ من لوحة SuperAdmin.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function integrationsItems(): array
    {
        return [
            $this->item('i-lucide-credit-card', 'Paymob Online Card Payments', 'مدفوعات Paymob الإلكترونية',
                'Hosted-checkout integration for online card collection — invoices and add-on purchases route to Paymob with HMAC-verified webhook capture.',
                'تكامل دفع مستضاف لتحصيل البطاقات أونلاين — الفواتير وعمليات شراء الإضافات توجَّه لـ Paymob مع تأكيد بـ HMAC للـ webhook.'),
            $this->item('i-lucide-receipt', 'Fawry Payment Codes', 'أكواد دفع فوري',
                'Generate Fawry payment codes for invoices and subscriptions — popular in Egypt for users without a credit card.',
                'إنشاء أكواد دفع فوري للفواتير والاشتراكات — منتشر في مصر للمستخدمين بدون بطاقة ائتمان.'),
            $this->item('i-lucide-message-square', 'Beon.chat WhatsApp + SMS', 'Beon.chat واتساب + SMS',
                'Send invoice reminders, payment receipts, and ad-hoc messages over WhatsApp or SMS — full conversation history in the Messaging tab.',
                'أرسل تذكيرات الفواتير، إيصالات الدفع، ورسائل عادية عبر WhatsApp أو SMS — سجل محادثات كامل في تبويب الرسائل.'),
            $this->item('i-lucide-shopping-cart', 'E-Commerce Sync', 'مزامنة التجارة الإلكترونية',
                'Connect Shopify, WooCommerce, or other stores via webhook — orders auto-create matching invoices and customers in the platform.',
                'اربط Shopify أو WooCommerce أو متاجر أخرى عبر webhook — الطلبات تُنشئ فواتير وعملاء مطابقين في المنصة تلقائياً.'),
            $this->item('i-lucide-files', 'Client Portal', 'بوابة العملاء',
                'Branded client portal with invoice viewing, document upload/download, secure messaging, and self-service payment.',
                'بوابة عملاء بعلامة تجارية مع مشاهدة الفواتير، رفع/تنزيل المستندات، مراسلة آمنة، ودفع ذاتي الخدمة.'),
            $this->item('i-lucide-upload', 'Data Import (CSV)', 'استيراد البيانات (CSV)',
                'Bulk import clients, chart of accounts, and opening balances from CSV — UTF-8 + Arabic safe, with per-row error reporting.',
                'استيراد جماعي للعملاء ودليل الحسابات والأرصدة الافتتاحية من CSV — آمن مع UTF-8 + العربية، مع تقارير أخطاء لكل صف.'),
            $this->item('i-lucide-webhook', 'Webhooks (50+ events)', 'Webhooks (أكثر من 50 حدث)',
                'Subscribe to invoice.created, payment.received, journal.posted, and dozens more — auto-disable after consecutive failures, with delivery history.',
                'اشترك في invoice.created و payment.received و journal.posted وعشرات أخرى — تعطيل تلقائي بعد إخفاقات متتالية، مع سجل التسليم.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function platformItems(): array
    {
        return [
            $this->item('i-lucide-shield', 'Strict Tenant Isolation', 'عزل المستأجرين الصارم',
                'Every model with tenant data uses a global scope that filters by `tenant_id` automatically — there is no path through the API or admin to read another tenant\'s row by accident.',
                'كل نموذج يحتوي بيانات المستأجر يستخدم نطاقاً عاماً يفلتر بـ tenant_id تلقائياً — لا يوجد مسار عبر API أو الإدارة لقراءة بيانات مستأجر آخر بالخطأ.'),
            $this->item('i-lucide-user-cog', 'Granular RBAC', 'صلاحيات دقيقة',
                'Spatie-permission backed roles with per-route + per-feature gating. The frontend Can component mirrors the backend permission set so menu items disappear for users who can\'t use them.',
                'أدوار مدعومة بـ Spatie-permission مع تحكم لكل مسار وميزة. مكوّن Can في الواجهة يعكس صلاحيات الخادم فتختفي عناصر القائمة للمستخدمين الذين لا يملكون الصلاحية.'),
            $this->item('i-lucide-layers-2', 'Plans + Add-ons', 'الخطط + الإضافات',
                '4 default plans (Free Trial, Starter, Professional, Enterprise) plus a marketplace of add-ons for limit boosts, feature unlocks, and credit packs (SMS, AI tokens).',
                '4 خطط افتراضية (تجربة، أساسي، احترافي، مؤسسات) بالإضافة لمتجر إضافات لرفع الحدود وفتح الميزات وحزم الرصيد (SMS، رموز AI).'),
            $this->item('i-lucide-gauge', 'Usage Metering & Projections', 'قياس الاستخدام والتوقعات',
                'Daily snapshots of users, clients, invoices, bills, journal entries, bank imports, documents, API calls, and storage. The SPA shows projected exhaust dates from the trailing 14-day trend.',
                'لقطات يومية للمستخدمين والعملاء والفواتير وفواتير الموردين والقيود واستيراد البنوك والمستندات وطلبات API والتخزين. الواجهة تعرض تواريخ النفاد المتوقعة من اتجاه آخر 14 يوماً.'),
            $this->item('i-lucide-mail-warning', 'Threshold Warnings', 'تنبيهات العتبات',
                'Tenant owners get email at 80% and 100% of every metered limit — idempotent per (tenant, metric, threshold, day) so the cron is safe to re-run.',
                'مالكو الحسابات يصلهم بريد عند 80% و100% من كل حد مقاس — idempotent لكل (مستأجر، مقياس، عتبة، يوم) فالـ cron آمن للإعادة.'),
            $this->item('i-lucide-key-square', 'Sanctum API Tokens', 'رموز Sanctum',
                'Per-user personal-access tokens with optional expiration and scoped abilities — usable from CI scripts, automations, or third-party integrations.',
                'رموز وصول شخصية لكل مستخدم مع انتهاء صلاحية اختياري وقدرات محددة — قابلة للاستخدام من سكربتات CI أو الأتمتة أو التكاملات.'),
            $this->item('i-lucide-history', 'Activity Log', 'سجل النشاط',
                'Spatie Activitylog records every model change with actor, IP, before/after JSON snapshot — searchable per entity from the firm dashboard.',
                'Spatie Activitylog يسجل كل تغيير في النموذج مع المنفّذ وعنوان IP ولقطة JSON قبل/بعد — قابل للبحث لكل كيان من لوحة المكتب.'),
            $this->item('i-lucide-user-check', 'Approval Workflows', 'سير العمل للاعتمادات',
                'Five entity types gated: journal entries, bill payments, invoices (post-to-GL), fiscal-period close, payroll runs. Per-tenant thresholds; entries below threshold auto-approve.',
                'خمسة أنواع كيانات مع بوابات: القيود، مدفوعات الموردين، الفواتير (الترحيل للقيد العام), إقفال الفترة المالية، مسيرات الرواتب. عتبات لكل مستأجر، الإدخالات تحت العتبة تُعتمد تلقائياً.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function hiddenItems(): array
    {
        return [
            $this->item('i-lucide-calculator', 'bcmath Money Helper', 'مساعد العملات bcmath', 'Hidden', 'خفي',
                'Every cent of arithmetic — VAT, profit shares, exchange rates, sums of debits/credits — runs through a bcmath wrapper. No float drift, ever. The journal-entry validator uses it for the balance check; payroll uses it for SI/tax math.',
                'كل قرش من الحسابات — الضريبة، حصص الأرباح، أسعار الصرف، مجاميع المدين/الدائن — يمر عبر wrapper لـ bcmath. لا انحراف عشري على الإطلاق. مدقق القيد اليومي يستخدمه لفحص التوازن، ومسيرات الرواتب لحسابات التأمينات والضرائب.'),
            $this->item('i-lucide-key', 'Idempotency Keys', 'مفاتيح Idempotency', 'Hidden', 'خفي',
                'Every state-changing endpoint accepts an Idempotency-Key header — duplicate requests within the window return the original response instead of double-charging or double-posting.',
                'كل نقطة تغيير حالة تقبل رأس Idempotency-Key — الطلبات المكررة داخل النافذة تُعيد الاستجابة الأصلية بدلاً من تكرار الخصم أو الترحيل.'),
            $this->item('i-lucide-shield-half', 'GL Reconciliation Safety Net', 'شبكة أمان مطابقة القيد العام', 'Hidden', 'خفي',
                'A nightly cron (`gl:reconcile`) walks every tenant\'s posted journal entries — reports header-vs-line variance, unbalanced posts, and exits non-zero so you can wire up alerting. Six tests guard the detection logic.',
                'cron ليلي (`gl:reconcile`) يمشي على القيود المُرحَّلة لكل مستأجر — يبلّغ عن فروق الرأس مقابل البنود، القيود غير المتوازنة، ويخرج بكود غير صفري لربط التنبيهات. ستة اختبارات تحمي منطق الكشف.'),
            $this->item('i-lucide-undo-2', 'Universal Soft Deletes', 'حذف ناعم شامل', 'Hidden', 'خفي',
                'Clients, invoices, bills, journal entries, employees, documents — all soft-deleted with restore endpoints. Filament SuperAdmin panel exposes archived records.',
                'العملاء، الفواتير، فواتير الموردين، القيود، الموظفون، المستندات — جميعها بحذف ناعم مع نقاط استعادة. لوحة Filament SuperAdmin تعرض السجلات المؤرشفة.'),
            $this->item('i-lucide-brain', 'Smart Account Suggestions', 'اقتراحات حسابات ذكية', 'Hidden', 'خفي',
                'Every journal-line description + account_id pair gets quietly recorded as a learning sample — next time a similar description appears, the picker pre-selects the most-frequent account match.',
                'كل وصف بند قيد + account_id يُسجَّل كعينة تعلّم بصمت — في المرة القادمة عند ظهور وصف مشابه، المنتقي يختار مسبقاً الحساب الأكثر تطابقاً.'),
            $this->item('i-lucide-flag', 'Per-Tenant Feature Flag Overrides', 'تجاوزات أعلام الميزات لكل مستأجر', 'Hidden', 'خفي',
                'Beyond plan bundles, the SuperAdmin can toggle individual features on/off per tenant — useful for early-access trials, support-led debug, or regulatory carve-outs.',
                'بجانب باقات الخطط، يمكن للـ SuperAdmin تفعيل/إيقاف ميزات فردية لكل مستأجر — مفيد لتجارب الوصول المبكر، تشخيص الدعم، أو استثناءات تنظيمية.'),
            $this->item('i-lucide-trending-up', 'Usage Exhaust Projection', 'توقع نفاد الاستخدام', 'Hidden', 'خفي',
                'Linear-trend projection from the trailing 14-day usage gives users a "limit in ~12 days" warning per metric, before they get blocked.',
                'توقع بالاتجاه الخطي من استخدام آخر 14 يوماً يعطي المستخدمين تنبيه "الحد خلال ~12 يوم" لكل مقياس قبل الحظر.'),
            $this->item('i-lucide-coins', 'Credit Pack Wallets', 'محافظ حزم الرصيد', 'Hidden', 'خفي',
                'Buy credit packs (SMS, AI tokens) — wallets drain FIFO across packs, with per-pack expiry. Lock-aware consumption prevents double-spend under concurrent requests.',
                'اشترِ حزم رصيد (SMS، رموز AI) — المحافظ تستنزف بنظام FIFO عبر الحزم مع انتهاء صلاحية لكل حزمة. الاستهلاك مُتزامن آمن لمنع الإنفاق المزدوج تحت الطلبات المتوازية.'),
            $this->item('i-lucide-file-warning', 'Fiscal Period Lock Enforcement', 'إنفاذ قفل الفترة المالية', 'Hidden', 'خفي',
                'Closing a fiscal period throws `FiscalPeriodLockedException` (HTTP 423) on any subsequent create/update/post/reverse — including reversals where the **target** period is locked, not the original.',
                'إقفال فترة مالية يُلقي FiscalPeriodLockedException (HTTP 423) على أي إنشاء/تحديث/ترحيل/عكس لاحق — بما في ذلك العكس عندما تكون الفترة الهدف مقفلة وليس الأصلية.'),
            $this->item('i-lucide-ban', 'Duplicate-Request Prevention', 'منع تكرار الطلبات', 'Hidden', 'خفي',
                'Beyond Idempotency-Key, a separate middleware blocks identical request bodies on cancel/refund-style endpoints when fired within seconds — protects against the user double-clicking through a slow UI.',
                'بجانب Idempotency-Key، middleware منفصل يحظر نفس جسم الطلب على نقاط إلغاء/استرجاع عند إطلاقها خلال ثوانٍ — يحمي من النقر المزدوج عبر واجهة بطيئة.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function securityItems(): array
    {
        return [
            $this->item('i-lucide-key-round', 'Two-Factor Authentication', 'مصادقة ثنائية',
                'TOTP-based 2FA with bcrypt-hashed recovery codes stored at rest inside the encrypted blob. Admin users are nudged to enroll on first login.',
                'مصادقة ثنائية بـ TOTP مع رموز استرداد مُجزَّأة بـ bcrypt مخزّنة داخل blob مشفّر. المستخدمون الإداريون يُحثّون على التسجيل عند أول تسجيل دخول.'),
            $this->item('i-lucide-ip', 'Admin IP Allow-List', 'قائمة IP مسموح بها للإدارة',
                'SuperAdmin panel can be locked behind a comma-separated IP allow-list (`ADMIN_IP_WHITELIST`) — blocks any logged-in admin from a non-whitelisted IP at the middleware layer.',
                'لوحة SuperAdmin يمكن قفلها خلف قائمة IP مسموح بها (ADMIN_IP_WHITELIST) — تحظر أي مشرف مسجَّل من IP غير مدرج في القائمة على مستوى middleware.'),
            $this->item('i-lucide-shield-alert', 'CORS Wildcard+Credentials Guard', 'حماية CORS من النجمة + الاعتماد',
                'Boot-time guard throws if `CORS_SUPPORTS_CREDENTIALS=true` is combined with `*` in `CORS_ALLOWED_ORIGINS` — that combination is browser-rejected per spec, so we fail loudly at boot instead of breaking SPA auth at runtime.',
                'حماية عند الإقلاع تُلقي خطأ إذا اجتمعت CORS_SUPPORTS_CREDENTIALS=true مع * في CORS_ALLOWED_ORIGINS — هذه التركيبة يرفضها المتصفح، نفشل بصوت عالٍ عند الإقلاع بدل تعطيل المصادقة وقت التشغيل.'),
            $this->item('i-lucide-fingerprint', 'HMAC-Verified Webhooks', 'Webhooks بتأكيد HMAC',
                'Paymob and Fawry callbacks both go through HMAC verification before doing anything that mutates state — invalid signatures are logged and rejected.',
                'ردود Paymob وفوري تمر عبر تأكيد HMAC قبل أي تغيير حالة — التوقيعات غير الصحيحة تُسجَّل وتُرفض.'),
            $this->item('i-lucide-lock', 'Money Field Validation', 'تحقق حقول العملات',
                'Every form-request validates money fields with `numeric, min, max:9999999999.99` — caps at 10 billion EGP per row, preventing 16-digit garbage from reaching DECIMAL(15,2) columns.',
                'كل طلب نموذج يدقق حقول العملات بـ numeric, min, max:9999999999.99 — حد أعلى 10 مليار جنيه لكل صف، يمنع أرقام بـ 16 خانة من الوصول لأعمدة DECIMAL(15,2).'),
            $this->item('i-lucide-database', 'Daily Database Backups', 'نسخ احتياطية يومية',
                'Encrypted daily backups with 30-day retention, run from the scheduler — the artifacts include a clean snapshot of every tenant\'s data.',
                'نسخ احتياطية يومية مشفّرة باحتفاظ 30 يوم تعمل من scheduler — الأرشيفات تشمل لقطة نظيفة لبيانات كل مستأجر.'),
            $this->item('i-lucide-rotate-ccw', 'Subscription Lifecycle Cron', 'cron دورة حياة الاشتراكات',
                'Nightly job expires trials, processes past-due subscriptions, suspends expired tenants, and emails users 3 days before period end — all idempotent, all logged.',
                'وظيفة ليلية تنهي التجارب، تعالج الاشتراكات المتأخرة، تعلّق المستأجرين المنتهيين، وترسل بريد للمستخدمين قبل 3 أيام من نهاية الفترة — كلها idempotent ومُسجَّلة.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function techStackItems(): array
    {
        return [
            $this->item('i-lucide-server', 'Laravel 13 + Filament 5', 'Laravel 13 + Filament 5',
                'PHP 8.3+ backend with the latest Laravel framework and Filament admin panel — 1,000+ Pest feature tests, PHPStan level 5 enforced on new code.',
                'خادم PHP 8.3+ مع أحدث إطار Laravel ولوحة إدارة Filament — أكثر من 1000 اختبار Pest، PHPStan مستوى 5 مُنفذ على الكود الجديد.'),
            $this->item('i-lucide-globe', 'Nuxt 4 + Vue 3.5 + Vite', 'Nuxt 4 + Vue 3.5 + Vite',
                'Modern SPA with auto-imports, file-based routing, Pinia stores, and Tailwind v4 with neutral design tokens — full dark mode and bilingual RTL support.',
                'SPA حديث مع استيراد تلقائي، توجيه ملفي، مخازن Pinia، وTailwind v4 مع رموز تصميم حيادية — وضع داكن كامل ودعم RTL ثنائي اللغة.'),
            $this->item('i-lucide-database', 'PostgreSQL + Redis', 'PostgreSQL + Redis',
                'Production stack uses PostgreSQL for ACID-compliant accounting data and Redis for cache + queue. Money columns use DECIMAL(15,2) — never floats.',
                'المنصة الإنتاجية تستخدم PostgreSQL لبيانات محاسبية ملتزمة بـ ACID وRedis للذاكرة المؤقتة والقوائم. أعمدة العملات تستخدم DECIMAL(15,2) — لا floats أبداً.'),
            $this->item('i-lucide-shield', 'Sanctum + Spatie + Activitylog', 'Sanctum + Spatie + Activitylog',
                'Laravel Sanctum tokens, Spatie Permission for RBAC, Spatie Activitylog for audit history — all battle-tested and well-maintained packages.',
                'رموز Laravel Sanctum، Spatie Permission للصلاحيات، Spatie Activitylog لسجل التدقيق — حزم مختبرة في الميدان ومحافظ عليها جيداً.'),
            $this->item('i-lucide-zap', 'Horizon + Queues', 'Horizon + قوائم انتظار',
                'Laravel Horizon manages prioritized queue workers — emails / reports / webhooks / maintenance — with a real-time dashboard for SuperAdmins.',
                'Laravel Horizon يدير عمال قوائم بأولويات — بريد / تقارير / webhooks / صيانة — مع لوحة فورية للمشرفين.'),
            $this->item('i-lucide-cloud', 'S3-Compatible Storage', 'تخزين متوافق مع S3',
                'Document uploads, payslips, and report exports go to S3 (or MinIO) with per-tenant key prefixing and SHA-256 deduplication.',
                'رفع المستندات وقسائم الرواتب وتصدير التقارير إلى S3 (أو MinIO) مع بادئات مفاتيح لكل مستأجر وإلغاء تكرار بـ SHA-256.'),
            $this->item('i-lucide-test-tube', 'Test Coverage', 'تغطية الاختبارات',
                'Pest test suite covers core accounting flows — 1,046 passing tests, 3,738 assertions. Money helper has dedicated mutation testing scaffolded (Infection).',
                'مجموعة اختبارات Pest تغطي مسارات المحاسبة الأساسية — 1046 اختبار ناجح، 3738 assertion. مساعد العملات له اختبار طفرات مخصص (Infection).'),
            $this->item('i-lucide-git-branch', 'CI/CD Ready', 'جاهز لـ CI/CD',
                'Type checks (`vue-tsc`), PHP lints, PHPStan, Pest tests, and migration dry-runs all run from the standard Laravel/Nuxt commands — drop into any CI pipeline.',
                'فحوص الأنواع (vue-tsc)، lint للـ PHP، PHPStan، اختبارات Pest، تجريب migrations — كلها تعمل من أوامر Laravel/Nuxt القياسية، يمكن إدراجها في أي خط CI.'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Builders
    // ─────────────────────────────────────────────────────────────

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function section(
        string $slug,
        string $icon,
        string $accent,
        string $titleEn,
        string $titleAr,
        string $subtitleEn,
        string $subtitleAr,
        array $items,
    ): array {
        return [
            'slug' => $slug,
            'icon' => $icon,
            'accent' => $accent,
            'title_en' => $titleEn,
            'title_ar' => $titleAr,
            'subtitle_en' => $subtitleEn,
            'subtitle_ar' => $subtitleAr,
            'items' => $items,
        ];
    }

    /**
     * Item builder with optional badge: pass `badgeEn` + `badgeAr` to flag
     * the row with a "Hidden" / "New" / "Pro" pill on the marketing page.
     *
     * @return array<string, mixed>
     */
    private function item(
        string $icon,
        string $titleEn,
        string $titleAr,
        ?string $badgeEnOrDescriptionEn,
        ?string $badgeArOrDescriptionAr = null,
        ?string $descriptionEn = null,
        ?string $descriptionAr = null,
    ): array {
        // Two-arg overload: item($icon, $titleEn, $titleAr, $descriptionEn, $descriptionAr)
        // becomes an item without a badge. Three-arg overload (with badge):
        // item($icon, $titleEn, $titleAr, $badgeEn, $badgeAr, $descEn, $descAr).
        if ($descriptionEn === null) {
            return [
                'icon' => $icon,
                'title_en' => $titleEn,
                'title_ar' => $titleAr,
                'description_en' => $badgeEnOrDescriptionEn ?? '',
                'description_ar' => $badgeArOrDescriptionAr ?? '',
                'badge_en' => null,
                'badge_ar' => null,
            ];
        }

        return [
            'icon' => $icon,
            'title_en' => $titleEn,
            'title_ar' => $titleAr,
            'description_en' => $descriptionEn,
            'description_ar' => $descriptionAr,
            'badge_en' => $badgeEnOrDescriptionEn,
            'badge_ar' => $badgeArOrDescriptionAr,
        ];
    }
}
