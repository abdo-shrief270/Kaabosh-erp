<?php

namespace Database\Seeders;

use App\Domain\Cms\Models\CmsPage;
use App\Domain\Cms\Models\Faq;
use App\Domain\Cms\Models\LandingSetting;
use App\Domain\Cms\Models\Testimonial;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLandingSettings();
        $this->seedPages();
        $this->seedTestimonials();
        $this->seedFaqs();
    }

    private function seedLandingSettings(): void
    {
        LandingSetting::updateOrCreate(['section' => 'hero'], ['data' => [
            'badge' => [
                'ar' => 'متوافق مع منظومة الفاتورة الإلكترونية المصرية',
                'en' => 'Egyptian E-Invoice (ETA) compliant',
            ],
            'title1' => ['ar' => 'نظام محاسبة سحابي', 'en' => 'Cloud Accounting'],
            'title2' => ['ar' => 'لمكاتب المحاسبة المصرية', 'en' => 'for Egyptian Firms'],
            'subtitle' => [
                'ar' => 'أدر عملاءك، فواتيرك، وقيودك المحاسبية من مكان واحد. نظام متكامل مصمم خصيصاً لمكاتب المحاسبة في مصر مع ربط مباشر بالفاتورة الإلكترونية.',
                'en' => 'Manage your clients, invoices, and journal entries from one place. A complete system designed specifically for accounting firms in Egypt with direct ETA integration.',
            ],
        ]]);

        LandingSetting::updateOrCreate(['section' => 'stats'], ['data' => [
            'firms' => '+500',
            'invoices' => '+10K',
            'uptime' => '99.9%',
        ]]);
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'slug' => 'terms',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms of Service',
                'meta_description_ar' => 'شروط وأحكام استخدام منصة محاسبي',
                'meta_description_en' => 'Terms of service for using the Muhasebi platform',
                'content_ar' => '<h2>مقدمة</h2><p>مرحباً بكم في محاسبي. باستخدامك لخدماتنا، فإنك توافق على الالتزام بهذه الشروط والأحكام.</p><h2>استخدام الخدمة</h2><p>يجب استخدام خدماتنا فقط للأغراض المحاسبية المشروعة وفقاً للقوانين المصرية المعمول بها.</p><h2>الحسابات</h2><p>أنت مسؤول عن الحفاظ على سرية بيانات تسجيل الدخول الخاصة بك وعن جميع الأنشطة التي تتم من خلال حسابك.</p><h2>الخصوصية والبيانات</h2><p>نحن نحترم خصوصيتك ونحمي بياناتك وفقاً لسياسة الخصوصية الخاصة بنا.</p><h2>إنهاء الخدمة</h2><p>يمكنك إلغاء اشتراكك في أي وقت. ستظل بياناتك متاحة لمدة 30 يوماً بعد الإلغاء.</p>',
                'content_en' => '<h2>Introduction</h2><p>Welcome to Muhasebi. By using our services, you agree to be bound by these terms and conditions.</p><h2>Use of Service</h2><p>Our services must only be used for legitimate accounting purposes in accordance with applicable Egyptian laws.</p><h2>Accounts</h2><p>You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account.</p><h2>Privacy & Data</h2><p>We respect your privacy and protect your data in accordance with our Privacy Policy.</p><h2>Termination</h2><p>You can cancel your subscription at any time. Your data will remain available for 30 days after cancellation.</p>',
                'is_published' => true,
            ],
            [
                'slug' => 'privacy',
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy Policy',
                'meta_description_ar' => 'سياسة الخصوصية وحماية البيانات في محاسبي',
                'meta_description_en' => 'Privacy policy and data protection at Muhasebi',
                'content_ar' => '<h2>جمع البيانات</h2><p>نقوم بجمع البيانات الضرورية فقط لتقديم خدماتنا المحاسبية، بما في ذلك بيانات الاتصال والبيانات المالية.</p><h2>استخدام البيانات</h2><p>نستخدم بياناتك فقط لتقديم وتحسين خدماتنا. لا نبيع أو نشارك بياناتك مع أطراف ثالثة.</p><h2>أمان البيانات</h2><p>نستخدم تشفير SSL 256-bit وخوادم محمية على مستوى البنوك مع نسخ احتياطية يومية تلقائية.</p><h2>حقوقك</h2><p>يمكنك طلب الوصول إلى بياناتك أو تعديلها أو حذفها في أي وقت عبر التواصل مع فريق الدعم.</p>',
                'content_en' => '<h2>Data Collection</h2><p>We collect only the data necessary to provide our accounting services, including contact information and financial data.</p><h2>Data Usage</h2><p>We use your data solely to provide and improve our services. We do not sell or share your data with third parties.</p><h2>Data Security</h2><p>We use 256-bit SSL encryption and bank-level secure servers with automatic daily backups.</p><h2>Your Rights</h2><p>You can request access to, modification of, or deletion of your data at any time by contacting our support team.</p>',
                'is_published' => true,
            ],
            [
                'slug' => 'changelog',
                'title_ar' => 'سجل التغييرات',
                'title_en' => 'Changelog',
                'meta_description_ar' => 'آخر التحديثات والتحسينات في محاسبي',
                'meta_description_en' => 'Latest updates and improvements in Muhasebi',
                'content_ar' => '<h2>الإصدار 2.4.0 — أبريل 2026</h2><h3>بوابة العملاء الجديدة</h3><ul><li>بوابة مخصصة لكل عميل لمتابعة فواتيره ومستنداته</li><li>نظام مراسلات مباشرة بين العميل ومكتب المحاسبة</li><li>إشعارات فورية للعملاء عند إصدار فاتورة جديدة</li></ul><h2>الإصدار 2.3.0 — مارس 2026</h2><h3>تحسينات الفاتورة الإلكترونية</h3><ul><li>دعم مطابقة الفواتير التلقائية مع ETA</li><li>تحسين سرعة إرسال الفواتير بنسبة 60%</li><li>إضافة تقارير حالة الفواتير الإلكترونية</li></ul>',
                'content_en' => '<h2>Version 2.4.0 — April 2026</h2><h3>New Client Portal</h3><ul><li>Dedicated portal for each client to track invoices and documents</li><li>Direct messaging system between client and accounting firm</li><li>Instant notifications for clients when new invoices are issued</li></ul><h2>Version 2.3.0 — March 2026</h2><h3>E-Invoicing Improvements</h3><ul><li>Automatic invoice reconciliation with ETA</li><li>60% faster invoice submission speed</li><li>Added e-invoice status reports</li></ul>',
                'is_published' => true,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            [
                'name_ar' => 'أحمد محمود', 'name_en' => 'Ahmed Mahmoud',
                'role_ar' => 'مدير مكتب محاسبة - القاهرة', 'role_en' => 'Managing Partner - Cairo',
                'quote_ar' => 'محاسبي غيّر طريقة عملنا بالكامل. أصبحنا ننجز في ساعة ما كان يأخذ يوماً كاملاً. الربط مع الفاتورة الإلكترونية وفّر علينا وقتاً هائلاً.',
                'quote_en' => 'Muhasebi completely transformed how we work. We now accomplish in an hour what used to take a full day. The ETA integration saved us tremendous time.',
                'rating' => 5, 'sort_order' => 1,
            ],
            [
                'name_ar' => 'سارة حسن', 'name_en' => 'Sarah Hassan',
                'role_ar' => 'محاسبة قانونية - الإسكندرية', 'role_en' => 'CPA - Alexandria',
                'quote_ar' => 'بوابة العملاء ميزة رائعة. عملاؤنا يتابعون فواتيرهم ومستنداتهم بأنفسهم مما قلل الاستفسارات بنسبة ٧٠٪. النظام سهل ومباشر.',
                'quote_en' => 'The client portal is amazing. Our clients track their invoices and documents themselves, reducing inquiries by 70%. The system is intuitive and straightforward.',
                'rating' => 5, 'sort_order' => 2,
            ],
            [
                'name_ar' => 'محمد عبدالرحمن', 'name_en' => 'Mohamed Abdelrahman',
                'role_ar' => 'مراجع حسابات - المنصورة', 'role_en' => 'Auditor - Mansoura',
                'quote_ar' => 'كنت أبحث عن نظام محاسبة يفهم السوق المصري فعلاً. محاسبي يدعم دليل الحسابات المصري والضرائب المحلية بشكل ممتاز. أنصح به بشدة.',
                'quote_en' => 'I was looking for an accounting system that truly understands the Egyptian market. Muhasebi supports the Egyptian chart of accounts and local taxes excellently.',
                'rating' => 5, 'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $t) {
            Testimonial::updateOrCreate(
                ['name_en' => $t['name_en']],
                $t,
            );
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['question_ar' => 'هل يمكنني تجربة النظام مجاناً قبل الاشتراك؟', 'question_en' => 'Can I try the system for free before subscribing?', 'answer_ar' => 'نعم! نوفر فترة تجريبية مجانية لمدة 14 يوماً بجميع المزايا بدون الحاجة لبطاقة ائتمان.', 'answer_en' => 'Yes! We offer a 14-day free trial with all features, no credit card required.', 'sort_order' => 1],
            ['question_ar' => 'هل النظام متوافق مع منظومة الفاتورة الإلكترونية المصرية؟', 'question_en' => 'Is the system compatible with the Egyptian e-invoicing system?', 'answer_ar' => 'بالتأكيد. محاسبي متوافق بالكامل مع منظومة الفاتورة الإلكترونية المصرية (ETA).', 'answer_en' => 'Absolutely. Muhasebi is fully compatible with the Egyptian Tax Authority e-invoicing system (ETA).', 'sort_order' => 2],
            ['question_ar' => 'هل بياناتي آمنة؟', 'question_en' => 'Is my data secure?', 'answer_ar' => 'أمان بياناتك أولويتنا القصوى. نستخدم تشفير SSL 256-bit وخوادم محمية على مستوى البنوك.', 'answer_en' => 'Your data security is our top priority. We use 256-bit SSL encryption and bank-level secure servers.', 'sort_order' => 3],
            ['question_ar' => 'هل يمكنني ترحيل بياناتي من نظام آخر؟', 'question_en' => 'Can I migrate my data from another system?', 'answer_ar' => 'نعم، يدعم محاسبي استيراد البيانات من ملفات Excel وCSV.', 'answer_en' => 'Yes, Muhasebi supports data import from Excel and CSV files.', 'sort_order' => 4],
            ['question_ar' => 'ما هي طرق الدفع المتاحة؟', 'question_en' => 'What payment methods are available?', 'answer_ar' => 'نقبل الدفع عبر بطاقات الائتمان (Visa/Mastercard)، التحويل البنكي، وفودافون كاش.', 'answer_en' => 'We accept credit cards (Visa/Mastercard), bank transfers, and Vodafone Cash.', 'sort_order' => 5],
            ['question_ar' => 'هل يدعم النظام اللغة العربية والإنجليزية؟', 'question_en' => 'Does the system support Arabic and English?', 'answer_ar' => 'نعم، محاسبي يدعم اللغتين العربية والإنجليزية بشكل كامل مع دعم RTL.', 'answer_en' => 'Yes, Muhasebi fully supports both Arabic and English with RTL support.', 'sort_order' => 6],
        ];

        foreach ($faqs as $f) {
            Faq::updateOrCreate(
                ['question_en' => $f['question_en']],
                $f,
            );
        }
    }
}
