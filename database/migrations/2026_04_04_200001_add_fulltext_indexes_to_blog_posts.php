<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add FULLTEXT indexes for MySQL/MariaDB
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->fullText(['title_ar', 'title_en', 'content_ar', 'content_en'], 'blog_posts_fulltext');
            });
        }
    }

    public function down(): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->dropFullText('blog_posts_fulltext');
            });
        }
    }
};
