<?php

namespace Database\Seeders;

use App\Domain\Blog\Models\BlogCategory;
use App\Domain\Blog\Models\BlogPost;
use App\Domain\Blog\Models\BlogTag;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            BlogCategory::updateOrCreate(['slug' => 'accounting'], ['name_ar' => 'المحاسبة', 'name_en' => 'Accounting', 'sort_order' => 1]),
            BlogCategory::updateOrCreate(['slug' => 'tax'], ['name_ar' => 'الضرائب', 'name_en' => 'Tax & Compliance', 'sort_order' => 2]),
            BlogCategory::updateOrCreate(['slug' => 'technology'], ['name_ar' => 'التكنولوجيا', 'name_en' => 'Technology', 'sort_order' => 3]),
            BlogCategory::updateOrCreate(['slug' => 'tips'], ['name_ar' => 'نصائح', 'name_en' => 'Tips & Guides', 'sort_order' => 4]),
        ];

        // Tags
        $tags = collect([
            ['slug' => 'eta', 'name_ar' => 'الفاتورة الإلكترونية', 'name_en' => 'E-Invoicing'],
            ['slug' => 'vat', 'name_ar' => 'ضريبة القيمة المضافة', 'name_en' => 'VAT'],
            ['slug' => 'cloud', 'name_ar' => 'سحابي', 'name_en' => 'Cloud'],
            ['slug' => 'reports', 'name_ar' => 'تقارير', 'name_en' => 'Reports'],
            ['slug' => 'automation', 'name_ar' => 'أتمتة', 'name_en' => 'Automation'],
            ['slug' => 'egypt', 'name_ar' => 'مصر', 'name_en' => 'Egypt'],
        ])->map(fn ($t) => BlogTag::updateOrCreate(['slug' => $t['slug']], $t));

        // Sample posts
        $posts = [
            [
                'slug' => 'guide-to-egyptian-e-invoicing',
                'category_id' => $categories[1]->id,
                'title_ar' => 'الدليل الشامل لمنظومة الفاتورة الإلكترونية المصرية',
                'title_en' => 'Complete Guide to Egyptian E-Invoicing System',
                'excerpt_ar' => 'كل ما تحتاج معرفته عن منظومة الفاتورة الإلكترونية في مصر وكيفية الالتزام بها.',
                'excerpt_en' => 'Everything you need to know about the Egyptian e-invoicing system and how to comply.',
                'content_ar' => '<h2>ما هي الفاتورة الإلكترونية؟</h2><p>الفاتورة الإلكترونية هي وثيقة رقمية تحل محل الفاتورة الورقية التقليدية. أطلقت مصلحة الضرائب المصرية منظومة الفاتورة الإلكترونية لتعزيز الشفافية وتقليل التهرب الضريبي.</p><h2>من يجب عليه الالتزام؟</h2><p>جميع الشركات المسجلة في مصلحة الضرائب المصرية ملزمة بإصدار فواتير إلكترونية عبر منظومة ETA.</p><h2>كيف يساعدك محاسبي؟</h2><p>محاسبي يوفر ربطاً مباشراً مع منظومة ETA، مما يتيح لك إصدار الفواتير الإلكترونية تلقائياً دون الحاجة لدخول بوابة الضرائب يدوياً.</p>',
                'content_en' => '<h2>What is E-Invoicing?</h2><p>E-invoicing is a digital document that replaces traditional paper invoices. The Egyptian Tax Authority launched the e-invoicing system to enhance transparency and reduce tax evasion.</p><h2>Who Must Comply?</h2><p>All companies registered with the Egyptian Tax Authority are required to issue electronic invoices through the ETA system.</p><h2>How Does Muhasebi Help?</h2><p>Muhasebi provides direct integration with the ETA system, allowing you to automatically issue e-invoices without manually accessing the tax portal.</p>',
                'author_name' => 'Muhasebi Team',
                'is_published' => true,
                'is_featured' => true,
                'published_at' => now()->subDays(5),
                'reading_time' => 5,
                'tag_slugs' => ['eta', 'egypt', 'vat'],
            ],
            [
                'slug' => 'cloud-accounting-benefits',
                'category_id' => $categories[2]->id,
                'title_ar' => '٧ أسباب تجعل المحاسبة السحابية ضرورة لمكتبك',
                'title_en' => '7 Reasons Cloud Accounting is Essential for Your Firm',
                'excerpt_ar' => 'اكتشف لماذا تتحول مكاتب المحاسبة المصرية إلى الأنظمة السحابية.',
                'excerpt_en' => 'Discover why Egyptian accounting firms are switching to cloud systems.',
                'content_ar' => '<h2>لماذا المحاسبة السحابية؟</h2><p>في عصر التحول الرقمي، أصبحت المحاسبة السحابية ضرورة وليست رفاهية. إليك أهم ٧ أسباب:</p><h3>1. الوصول من أي مكان</h3><p>اعمل من المكتب أو المنزل أو حتى أثناء التنقل. بياناتك متاحة دائماً.</p><h3>2. أمان أعلى</h3><p>بياناتك مشفرة ومحمية بنسخ احتياطية تلقائية يومية.</p><h3>3. تكلفة أقل</h3><p>لا حاجة لشراء خوادم أو صيانة أجهزة. ادفع فقط مقابل ما تستخدمه.</p>',
                'content_en' => '<h2>Why Cloud Accounting?</h2><p>In the age of digital transformation, cloud accounting has become a necessity, not a luxury. Here are the top 7 reasons:</p><h3>1. Access from Anywhere</h3><p>Work from the office, home, or on the go. Your data is always available.</p><h3>2. Better Security</h3><p>Your data is encrypted and protected with automatic daily backups.</p><h3>3. Lower Cost</h3><p>No need to buy servers or maintain hardware. Pay only for what you use.</p>',
                'author_name' => 'Muhasebi Team',
                'is_published' => true,
                'is_featured' => true,
                'published_at' => now()->subDays(12),
                'reading_time' => 4,
                'tag_slugs' => ['cloud', 'automation'],
            ],
            [
                'slug' => 'financial-reports-explained',
                'category_id' => $categories[0]->id,
                'title_ar' => 'فهم التقارير المالية: دليل مبسط للمحاسبين',
                'title_en' => 'Understanding Financial Reports: A Simplified Guide',
                'excerpt_ar' => 'شرح مبسط لأهم التقارير المالية وكيفية قراءتها وتحليلها.',
                'excerpt_en' => 'A simple explanation of key financial reports and how to read and analyze them.',
                'content_ar' => '<h2>التقارير المالية الأساسية</h2><p>التقارير المالية هي اللغة التي يتحدث بها المحاسبون. فهمها ضروري لاتخاذ قرارات مالية صحيحة.</p><h3>ميزان المراجعة</h3><p>يعرض أرصدة جميع الحسابات في فترة معينة للتأكد من توازن الدفاتر.</p><h3>قائمة الدخل</h3><p>توضح الإيرادات والمصروفات وصافي الربح أو الخسارة في فترة محددة.</p>',
                'content_en' => '<h2>Core Financial Reports</h2><p>Financial reports are the language accountants speak. Understanding them is essential for making sound financial decisions.</p><h3>Trial Balance</h3><p>Shows balances of all accounts in a specific period to ensure the books are balanced.</p><h3>Income Statement</h3><p>Shows revenues, expenses, and net profit or loss for a specific period.</p>',
                'author_name' => 'Muhasebi Team',
                'is_published' => true,
                'is_featured' => false,
                'published_at' => now()->subDays(20),
                'reading_time' => 6,
                'tag_slugs' => ['reports', 'egypt'],
            ],
        ];

        foreach ($posts as $postData) {
            $tagSlugs = $postData['tag_slugs'] ?? [];
            unset($postData['tag_slugs']);

            $post = BlogPost::updateOrCreate(['slug' => $postData['slug']], $postData);

            if ($tagSlugs) {
                $tagIds = BlogTag::whereIn('slug', $tagSlugs)->pluck('id');
                $post->tags()->sync($tagIds);
            }
        }
    }
}
