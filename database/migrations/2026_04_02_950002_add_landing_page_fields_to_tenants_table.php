<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('logo_path');
            $table->text('description')->nullable()->after('tagline');
            $table->string('primary_color', 7)->nullable()->default('#2c3e50')->after('description');
            $table->string('secondary_color', 7)->nullable()->default('#3498db')->after('primary_color');
            $table->string('hero_image_path')->nullable()->after('secondary_color');
            $table->boolean('is_landing_page_active')->default(false)->after('hero_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'description',
                'primary_color',
                'secondary_color',
                'hero_image_path',
                'is_landing_page_active',
            ]);
        });
    }
};
