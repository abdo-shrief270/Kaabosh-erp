<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'blog_posts',
            'blog_categories',
            'blog_tags',
            'cms_pages',
            'faqs',
            'contact_submissions',
            'testimonials',
            'email_templates',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'blog_posts',
            'blog_categories',
            'blog_tags',
            'cms_pages',
            'faqs',
            'contact_submissions',
            'testimonials',
            'email_templates',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
