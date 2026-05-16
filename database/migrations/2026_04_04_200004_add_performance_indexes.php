<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Blog posts - composite indexes for common queries
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index(['is_published', 'published_at'], 'blog_posts_published_idx');
            $table->index(['is_published', 'is_featured', 'published_at'], 'blog_posts_featured_idx');
            $table->index(['category_id', 'is_published', 'published_at'], 'blog_posts_category_idx');
        });

        // CMS pages - published + slug lookup
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->index(['is_published', 'slug'], 'cms_pages_published_slug_idx');
        });

        // Testimonials - active + sorted
        Schema::table('testimonials', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'testimonials_active_sort_idx');
        });

        // FAQs - active + sorted
        Schema::table('faqs', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'faqs_active_sort_idx');
        });

        // Contact submissions - status + date
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'contacts_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_posts_published_idx');
            $table->dropIndex('blog_posts_featured_idx');
            $table->dropIndex('blog_posts_category_idx');
        });
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropIndex('cms_pages_published_slug_idx');
        });
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropIndex('testimonials_active_sort_idx');
        });
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('faqs_active_sort_idx');
        });
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->dropIndex('contacts_status_date_idx');
        });
    }
};
